<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Wave 6.1 / P1-S2 + P1-S3 + P1-S4.
 *
 * Single source of truth for transitioning an Order between statuses.
 *
 * Responsibilities:
 *  - Enforce the order status FSM (rejects illegal transitions, e.g. anything
 *    out of `cancelled`).
 *  - Record every transition in `order_status_history` so support staff can
 *    answer "who/when/why".
 *  - Audit-log to admin_activity_log via AdminActivityLogger.
 *  - On transition TO `cancelled`, perform the cancellation side-effects
 *    atomically: restock product/variant inventory and decrement the coupon
 *    usage counter. Idempotent: `orders.restocked_at` guards against double
 *    application, so an admin flipping paid → cancelled → paid → cancelled
 *    cannot oversubtract.
 *
 * The service never auto-revokes any customer entitlement (only the order's
 * status changes). Stock and coupon adjustments are observable side-effects of
 * an explicit admin decision and are surfaced in side_effects on the history
 * row for full traceability.
 */
class OrderStatusService
{
    /**
     * @var array<string, list<string>>
     */
    public const TRANSITIONS = [
        Order::STATUS_PENDING => [
            Order::STATUS_PAID,
            Order::STATUS_PROCESSING,
            Order::STATUS_CANCELLED,
        ],
        Order::STATUS_PAID => [
            Order::STATUS_PROCESSING,
            Order::STATUS_SHIPPED,
            Order::STATUS_COMPLETED,
            Order::STATUS_CANCELLED,
        ],
        Order::STATUS_PROCESSING => [
            Order::STATUS_SHIPPED,
            Order::STATUS_COMPLETED,
            Order::STATUS_CANCELLED,
        ],
        Order::STATUS_SHIPPED => [
            Order::STATUS_COMPLETED,
            Order::STATUS_CANCELLED,
        ],
        Order::STATUS_COMPLETED => [
            Order::STATUS_CANCELLED,
        ],
        Order::STATUS_CANCELLED => [],
    ];

    public function canTransition(string $from, string $to): bool
    {
        if (! in_array($to, Order::STATUSES, true)) {
            return false;
        }
        if ($from === $to) {
            return false;
        }

        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    /**
     * Transition an order to a new status atomically.
     *
     * @return array{order: Order, history: OrderStatusHistory, side_effects: array<string, mixed>}
     */
    public function transition(
        Order $order,
        string $toStatus,
        ?int $changedByUserId = null,
        ?string $reason = null
    ): array {
        if (! in_array($toStatus, Order::STATUSES, true)) {
            throw new InvalidArgumentException('وضعیت سفارش نامعتبر است: ' . $toStatus);
        }

        return DB::transaction(function () use ($order, $toStatus, $changedByUserId, $reason) {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $fromStatus = (string) $locked->status;

            if ($fromStatus === $toStatus) {
                throw new InvalidArgumentException('سفارش هم‌اکنون در همین وضعیت قرار دارد.');
            }

            if (! $this->canTransition($fromStatus, $toStatus)) {
                throw new InvalidArgumentException(
                    'تغییر وضعیت از «' . $fromStatus . '» به «' . $toStatus . '» مجاز نیست.'
                );
            }

            $sideEffects = [];

            if ($toStatus === Order::STATUS_CANCELLED) {
                $sideEffects = $this->applyCancellationSideEffects($locked);
            }

            $locked->update([
                'status' => $toStatus,
                'restocked_at' => $locked->restocked_at,
            ]);

            $history = OrderStatusHistory::query()->create([
                'order_id' => $locked->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'changed_by_user_id' => $changedByUserId,
                'reason' => $reason,
                'side_effects' => $sideEffects ?: null,
                'created_at' => now(),
            ]);

            AdminActivityLogger::log(
                action: 'order.status_changed',
                description: 'تغییر وضعیت سفارش ' . $locked->number . ' از ' . $fromStatus . ' به ' . $toStatus,
                subject: $locked,
                properties: [
                    'order_number' => $locked->number,
                    'from_status' => $fromStatus,
                    'to_status' => $toStatus,
                    'reason' => $reason,
                    'side_effects' => $sideEffects,
                ]
            );

            return [
                'order' => $locked->refresh(),
                'history' => $history,
                'side_effects' => $sideEffects,
            ];
        }, 3);
    }

    /**
     * Apply the cancellation side-effects (restock + coupon decrement) exactly
     * once per order. Mutates `$order` in-memory; caller saves it.
     *
     * @return array<string, mixed>
     */
    protected function applyCancellationSideEffects(Order $order): array
    {
        if ($order->restocked_at !== null) {
            return [
                'restocked' => false,
                'reason' => 'already_restocked',
                'previously_restocked_at' => $order->restocked_at->toIso8601String(),
            ];
        }

        $restockedItems = [];
        foreach ($order->items()->get() as $item) {
            $variantId = $item->product_variant_id;
            $qty = (int) $item->quantity;
            if ($qty <= 0) {
                continue;
            }

            if ($variantId) {
                $variant = ProductVariant::query()->whereKey($variantId)->lockForUpdate()->first();
                if ($variant && $variant->stock !== null) {
                    $variant->increment('stock', $qty);
                    $restockedItems[] = [
                        'product_id' => $item->product_id,
                        'product_variant_id' => $variantId,
                        'quantity' => $qty,
                        'type' => 'variant',
                    ];
                }
                continue;
            }

            if ($item->product_id) {
                $product = Product::query()->whereKey($item->product_id)->lockForUpdate()->first();
                if ($product && $product->stock !== null) {
                    $product->increment('stock', $qty);
                    $restockedItems[] = [
                        'product_id' => $product->id,
                        'product_variant_id' => null,
                        'quantity' => $qty,
                        'type' => 'product',
                    ];
                }
            }
        }

        $couponDecrement = null;
        if ($order->coupon_id) {
            $coupon = $order->coupon()->lockForUpdate()->first();
            if ($coupon && (int) $coupon->used_count > 0) {
                $coupon->decrement('used_count');
                $couponDecrement = [
                    'coupon_id' => $coupon->id,
                    'code' => $coupon->code,
                ];
            }
        }

        $order->restocked_at = now();

        return [
            'restocked' => true,
            'restocked_items' => $restockedItems,
            'coupon_usage_decremented' => $couponDecrement,
        ];
    }
}

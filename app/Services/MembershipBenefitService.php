<?php

namespace App\Services;

use App\Dto\MembershipDiscountResult;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class MembershipBenefitService
{
    private const CACHE_TTL = 300; // 5 minutes

    public function __construct(private readonly SubscriptionService $subscriptionService) {}

    /**
     * Get the active plan for a user, cached for 5 minutes.
     */
    public function getActivePlan(User $user): ?SubscriptionPlan
    {
        return Cache::remember(
            "membership_plan_{$user->id}",
            self::CACHE_TTL,
            fn () => $this->subscriptionService->getActivePlan($user)?->load('products')
        );
    }

    /**
     * Invalidate the cached plan for a user (call after subscribe/cancel/expire).
     */
    public function invalidateCache(User $user): void
    {
        Cache::forget("membership_plan_{$user->id}");
    }

    /**
     * Calculate the full membership discount result for a given cart and user.
     *
     * @param  array<string, array<string, mixed>>  $cart  Normalized cart from ShopCartHelper
     * @param  int  $subtotal  Cart subtotal in Toman (before any discount)
     */
    public function calculate(?User $user, array $cart, int $subtotal): MembershipDiscountResult
    {
        if (! $user || ! $user->id) {
            return MembershipDiscountResult::empty();
        }

        $plan = $this->getActivePlan($user);

        if (! $plan) {
            return MembershipDiscountResult::empty();
        }

        $discountAmount   = 0;
        $appliedProductIds = [];

        if ((float) $plan->product_discount_percent > 0) {
            if ($plan->apply_to_all_products) {
                $discountAmount    = (int) round($subtotal * ($plan->product_discount_percent / 100));
                $appliedProductIds = array_map(
                    fn ($row) => (int) $row['product_id'],
                    array_values($cart)
                );
            } else {
                [$discountAmount, $appliedProductIds] = $this->calculateProductScopedDiscount(
                    $plan,
                    $cart
                );
            }
        }

        return new MembershipDiscountResult(
            discountAmount:    min($discountAmount, $subtotal),
            discountPercent:   (float) $plan->product_discount_percent,
            freeShipping:      (bool) $plan->free_shipping,
            vipSupport:        (bool) $plan->vip_support,
            earlyAccess:       (bool) $plan->early_access,
            appliedToAll:      (bool) $plan->apply_to_all_products,
            appliedProductIds: array_values(array_unique($appliedProductIds)),
            planName:          $plan->name,
            plan:              $plan,
        );
    }

    /**
     * Quick check: does the user have free shipping on their active plan?
     */
    public function hasFreeShipping(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return (bool) ($this->getActivePlan($user)?->free_shipping ?? false);
    }

    /**
     * Quick check: does the user have VIP support on their active plan?
     */
    public function hasVipSupport(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return (bool) ($this->getActivePlan($user)?->vip_support ?? false);
    }

    /**
     * Quick check: does the user have early access on their active plan?
     */
    public function hasEarlyAccess(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return (bool) ($this->getActivePlan($user)?->early_access ?? false);
    }

    // ─── Private ─────────────────────────────────────────────────────────────

    /**
     * Calculate discount only for products explicitly linked to the plan.
     *
     * @return array{0: int, 1: list<int>}
     */
    private function calculateProductScopedDiscount(SubscriptionPlan $plan, array $cart): array
    {
        $planProducts = $plan->products->keyBy('id');

        if ($planProducts->isEmpty()) {
            return [0, []];
        }

        $discountAmount    = 0;
        $appliedProductIds = [];

        foreach ($cart as $row) {
            $productId = (int) ($row['product_id'] ?? 0);

            if (! $productId || ! $planProducts->has($productId)) {
                continue;
            }

            $product      = $planProducts->get($productId);
            $pivotPercent = (float) ($product->pivot->discount_percent ?? 0);
            $rate         = $pivotPercent > 0 ? $pivotPercent : (float) $plan->product_discount_percent;

            if ($rate <= 0) {
                continue;
            }

            $lineTotal      = (int) $row['price'] * (int) $row['quantity'];
            $discountAmount += (int) round($lineTotal * ($rate / 100));
            $appliedProductIds[] = $productId;
        }

        return [$discountAmount, $appliedProductIds];
    }
}

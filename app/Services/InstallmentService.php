<?php

namespace App\Services;

use App\Models\InstallmentOrder;
use App\Models\InstallmentPayment;
use App\Models\InstallmentPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InstallmentService
{
    /**
     * Get eligible plans for a given amount.
     */
    public function eligiblePlans(int $amount): \Illuminate\Database\Eloquent\Collection
    {
        return InstallmentPlan::active()->get()->filter(fn ($p) => $p->isEligible($amount))->values();
    }

    /**
     * Create a pending installment order (before down-payment gateway).
     */
    public function createOrder(User $user, InstallmentPlan $plan, int $totalAmount, ?int $orderId = null): InstallmentOrder
    {
        $calc = $plan->calculate($totalAmount);

        return DB::transaction(function () use ($user, $plan, $totalAmount, $orderId, $calc) {
            return InstallmentOrder::create([
                'order_id'            => $orderId,
                'user_id'             => $user->id,
                'plan_id'             => $plan->id,
                'total_amount'        => $totalAmount,
                'down_payment'        => $calc['down_payment'],
                'remaining_amount'    => $calc['remaining'],
                'monthly_installment' => $calc['monthly_payment'],
                'installments_total'  => $plan->period_months,
                'installments_paid'   => 0,
                'status'              => InstallmentOrder::STATUS_PENDING,
            ]);
        });
    }

    /**
     * Activate order after successful down-payment.
     */
    public function activateOrder(InstallmentOrder $order, string $gateway, string $ref): void
    {
        DB::transaction(function () use ($order, $gateway, $ref) {
            $order->update([
                'status'      => InstallmentOrder::STATUS_ACTIVE,
                'gateway_ref' => $ref,
                'start_date'  => now()->toDateString(),
            ]);
            $order->createPaymentSchedule();
        });
    }

    /**
     * Process a gateway callback for a single installment payment.
     */
    public function recordInstallmentPayment(
        InstallmentOrder $order,
        int $paymentId,
        string $gateway,
        string $ref
    ): bool {
        $payment = InstallmentPayment::find($paymentId);
        if (!$payment || $payment->installment_order_id !== $order->id) {
            return false;
        }
        if ($payment->status === InstallmentPayment::STATUS_PAID) {
            return true;
        }

        $order->markPaymentPaid($payment, $gateway, $ref);

        // FINANCE_LEDGER: push installment payment event to platform ledger
        try {
            app(\App\Services\PlatformApiService::class)->pushLedgerEvent([
                'event_type'     => 'installment_payment',
                'amount'         => (int) $payment->amount,
                'description'    => "قسط شماره {$payment->installment_number} از سفارش #{$order->id}",
                'reference_type' => 'installment_payments',
                'reference_id'   => $payment->id,
                'gateway'        => $gateway,
                'gateway_ref'    => $ref,
                'invoice_number' => null,
                'entry_date'     => now()->toDateString(),
                'transaction_at' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('LedgerPush: installment_payment failed', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
        }

        return true;
    }

    /**
     * Mark overdue installments.
     */
    public function markOverdue(): int
    {
        return InstallmentPayment::where('status', InstallmentPayment::STATUS_PENDING)
            ->where('due_date', '<', now()->toDateString())
            ->update(['status' => InstallmentPayment::STATUS_OVERDUE]);
    }
}

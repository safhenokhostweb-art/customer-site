<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionLog;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SaaSPlatform\Modules\Tickets\Models\Ticket;

class SubscriptionService
{
    public function __construct(private SmsService $sms) {}

    /**
     * Subscribe a user to a plan (free plans activate immediately).
     */
    public function subscribe(User $user, SubscriptionPlan $plan, ?int $invoiceId = null): Subscription
    {
        return DB::transaction(function () use ($user, $plan, $invoiceId) {
            $now = now();

            if ($plan->hasTrial() && $invoiceId === null) {
                $status    = Subscription::STATUS_TRIAL;
                $startsAt  = $now;
                $endsAt    = $now->copy()->addDays($plan->getDurationInDays());
                $trialEnds = $now->copy()->addDays($plan->trial_days);
            } else {
                $status    = Subscription::STATUS_ACTIVE;
                $startsAt  = $now;
                $endsAt    = $now->copy()->addDays($plan->getDurationInDays());
                $trialEnds = null;
            }

            $subscription = Subscription::create([
                'user_id'              => $user->id,
                'subscription_plan_id' => $plan->id,
                'status'               => $status,
                'starts_at'            => $startsAt,
                'ends_at'              => $endsAt,
                'trial_ends_at'        => $trialEnds,
                'invoice_id'           => $invoiceId,
            ]);

            $action = $plan->hasTrial() && $invoiceId === null
                ? SubscriptionLog::ACTION_TRIAL
                : SubscriptionLog::ACTION_SUBSCRIBED;

            $this->log($subscription, $action, $user->id, 'user', [
                'plan'  => $plan->name,
                'price' => $plan->price,
            ]);

            $this->notifySubscribed($user, $plan, $subscription);
            $this->createActivationTicket($user, $plan, $subscription);

            return $subscription;
        });
    }

    /**
     * ایجاد subscription با وضعیت pending (در انتظار تأیید ادمین و پرداخت).
     */
    public function createPendingSubscription(User $user, SubscriptionPlan $plan, ?int $invoiceId = null): Subscription
    {
        return DB::transaction(function () use ($user, $plan, $invoiceId) {
            $now = now();

            $subscription = Subscription::create([
                'user_id'              => $user->id,
                'subscription_plan_id' => $plan->id,
                'status'               => Subscription::STATUS_PENDING,
                'starts_at'            => $now,
                'ends_at'              => $now->copy()->addDays($plan->getDurationInDays()),
                'invoice_id'           => $invoiceId,
                'pending_reason'       => 'در انتظار پرداخت و تأیید مدیریت',
            ]);

            $this->log($subscription, 'pending_created', $user->id, 'user', [
                'plan'  => $plan->name,
                'price' => $plan->getEffectivePrice(),
            ]);

            return $subscription;
        });
    }

    /**
     * Cancel a subscription.
     */
    public function cancel(Subscription $subscription, ?string $reason = null, string $performedBy = 'user'): bool
    {
        if ($subscription->isCancelled()) {
            return false;
        }

        $subscription->update([
            'status'              => Subscription::STATUS_CANCELLED,
            'cancelled_at'        => now(),
            'cancellation_reason' => $reason,
        ]);

        $this->log($subscription, SubscriptionLog::ACTION_CANCELLED, $subscription->user_id, $performedBy, [
            'reason' => $reason,
        ]);

        $this->notifyCancelled($subscription->user, $subscription->plan);

        return true;
    }

    /**
     * Renew an active/expired subscription.
     */
    public function renew(Subscription $subscription, ?int $invoiceId = null): Subscription
    {
        return DB::transaction(function () use ($subscription, $invoiceId) {
            $plan    = $subscription->plan;
            $baseDate = ($subscription->isActive() && $subscription->ends_at?->isFuture())
                ? $subscription->ends_at
                : now();

            $subscription->update([
                'status'     => Subscription::STATUS_ACTIVE,
                'ends_at'    => $baseDate->copy()->addDays($plan->getDurationInDays()),
                'renewed_at' => now(),
                'invoice_id' => $invoiceId ?? $subscription->invoice_id,
            ]);

            $this->log($subscription, SubscriptionLog::ACTION_RENEWED, $subscription->user_id, 'user', [
                'plan'     => $plan->name,
                'new_ends' => $subscription->fresh()->ends_at->toDateString(),
            ]);

            $this->notifyRenewed($subscription->user, $plan, $subscription->fresh());

            return $subscription->fresh();
        });
    }

    /**
     * Expire a subscription (called by scheduler).
     */
    public function expire(Subscription $subscription): bool
    {
        if ($subscription->status === Subscription::STATUS_EXPIRED) {
            return false;
        }

        $subscription->update(['status' => Subscription::STATUS_EXPIRED]);

        $this->log($subscription, SubscriptionLog::ACTION_EXPIRED, null, 'system');

        $this->notifyExpired($subscription->user, $subscription->plan);

        return true;
    }

    /**
     * Expire all overdue active subscriptions (used by scheduler command).
     */
    public function expireOverdue(): int
    {
        $expired = Subscription::whereIn('status', [
                Subscription::STATUS_ACTIVE,
                Subscription::STATUS_TRIAL,
            ])
            ->where('ends_at', '<', now())
            ->get();

        $count = 0;
        foreach ($expired as $sub) {
            $this->expire($sub);
            $count++;
        }

        return $count;
    }

    /**
     * Get the user's current active subscription (most recent).
     */
    public function getActive(User $user): ?Subscription
    {
        return Subscription::where('user_id', $user->id)
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIAL])
            ->where('ends_at', '>', now())
            ->with('plan')
            ->latest('starts_at')
            ->first();
    }

    /**
     * Get user's active plan (shortcut).
     */
    public function getActivePlan(User $user): ?SubscriptionPlan
    {
        return $this->getActive($user)?->plan;
    }

    /**
     * Check if user has an active subscription to a specific plan.
     */
    public function hasActivePlan(User $user, int $planId): bool
    {
        return Subscription::where('user_id', $user->id)
            ->where('subscription_plan_id', $planId)
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIAL])
            ->where('ends_at', '>', now())
            ->exists();
    }

    /**
     * Create a subscription invoice in the invoices table.
     */
    public function createInvoice(User $user, SubscriptionPlan $plan): \App\Models\Invoice
    {
        $number = 'SUB-' . now()->format('Ymd') . '-' . str_pad(
            \App\Models\Invoice::whereDate('created_at', today())->count() + 1,
            4, '0', STR_PAD_LEFT
        );

        $invoice = \App\Models\Invoice::create([
            'invoice_number'   => $number,
            'user_id'          => $user->id,
            'customer_name'    => $user->name ?? $user->mobile,
            'customer_mobile'  => $user->mobile,
            'subtotal'         => $plan->price,
            'total'            => $plan->price,
            'status'           => 'pending',
        ]);

        \App\Models\InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'title'      => 'اشتراک پلن ' . $plan->name . ' (' . $plan->getDurationLabel() . ')',
            'price'      => $plan->price,
            'quantity'   => 1,
            'total'      => $plan->price,
        ]);

        return $invoice;
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    private function log(
        Subscription $subscription,
        string $action,
        ?int $userId,
        string $performedBy,
        array $details = []
    ): void {
        try {
            SubscriptionLog::create([
                'subscription_id' => $subscription->id,
                'user_id'         => $userId,
                'action'          => $action,
                'details'         => $details ?: null,
                'performed_by'    => $performedBy,
            ]);
        } catch (\Throwable $e) {
            Log::error('SubscriptionLog write failed', ['error' => $e->getMessage()]);
        }
    }

    private function notifySubscribed(User $user, SubscriptionPlan $plan, Subscription $sub): void
    {
        if (! $user->mobile) return;
        $ends = \App\Helpers\Jalali::formatFa($sub->ends_at, 'Y/m/d');
        $this->sms->send(
            $user->mobile,
            "سلام {$user->name}\nاشتراک «{$plan->name}» شما با موفقیت فعال شد.\nتاریخ انقضا: {$ends}\nبرای جزئیات وارد پنل کاربری شوید."
        );
    }

    private function createActivationTicket(User $user, SubscriptionPlan $plan, Subscription $sub): void
    {
        try {
            $ends = \App\Helpers\Jalali::formatFa($sub->ends_at, 'Y/m/d');
            $statusLabel = $sub->status === Subscription::STATUS_TRIAL ? 'دوره آزمایشی' : 'اشتراک';

            Ticket::withoutTenantIsolation()->create([
                'ticket_number'   => 'SUB-' . now()->format('YmdHis') . '-' . $user->id,
                'subject'         => "{$statusLabel} «{$plan->name}» برای شما فعال شد",
                'description'     => "سلام {$user->name} عزیز،\n\n{$statusLabel} «{$plan->name}» شما با موفقیت فعال گردید.\n\nجزئیات:\n• نام پلن: {$plan->name}\n• مدت: {$plan->getDurationLabel()}\n• تاریخ انقضا: {$ends}\n\nدر صورت هرگونه سوال یا مشکل، از همین تیکت پاسخ دهید.\nپشتیبانی",
                'priority'        => 'normal',
                'category'        => 'subscription',
                'status'          => Ticket::STATUS_OPEN,
                'customer_id'     => $user->id,
                'customer_name'   => $user->name,
                'customer_mobile' => $user->mobile ?? null,
                'site_id'         => null,
            ]);
        } catch (\Throwable $e) {
            Log::error('SubscriptionService: failed to create activation ticket', ['error' => $e->getMessage()]);
        }
    }

    private function notifyCancelled(User $user, ?SubscriptionPlan $plan): void
    {
        if (! $user?->mobile || ! $plan) return;
        $this->sms->send($user->mobile, "اشتراک {$plan->name} شما لغو شد.");
    }

    private function notifyRenewed(User $user, SubscriptionPlan $plan, Subscription $sub): void
    {
        if (! $user->mobile) return;
        $ends = \App\Helpers\Jalali::formatFa($sub->ends_at, 'Y/m/d');
        $this->sms->send(
            $user->mobile,
            "اشتراک {$plan->name} شما تمدید شد.\nتاریخ انقضای جدید: {$ends}"
        );
    }

    private function notifyExpired(User $user, ?SubscriptionPlan $plan): void
    {
        if (! $user?->mobile || ! $plan) return;
        $this->sms->send(
            $user->mobile,
            "اشتراک {$plan->name} شما منقضی شد.\nبرای تمدید وارد پنل کاربری شوید."
        );
    }
}

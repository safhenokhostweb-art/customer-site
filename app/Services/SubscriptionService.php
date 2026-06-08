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
use App\Services\WebhookDispatchService;

class SubscriptionService
{
    // Maps SubscriptionLog actions to webhook event types
    private const WEBHOOK_EVENT_MAP = [
        SubscriptionLog::ACTION_SUBSCRIBED  => 'subscription.created',
        SubscriptionLog::ACTION_TRIAL       => 'subscription.created',
        SubscriptionLog::ACTION_RENEWED     => 'subscription.renewed',
        SubscriptionLog::ACTION_AUTO_RENEWED => 'subscription.renewed',
        SubscriptionLog::ACTION_UPGRADED    => 'subscription.upgraded',
        SubscriptionLog::ACTION_DOWNGRADED  => 'subscription.downgraded',
        SubscriptionLog::ACTION_CANCELLED   => 'subscription.cancelled',
        SubscriptionLog::ACTION_EXPIRED     => 'subscription.expired',
    ];

    public function __construct(
        private SmsService $sms,
        private WebhookDispatchService $webhooks,
    ) {}

    /**
     * Subscribe a user to a plan (free plans activate immediately).
     */
    public function subscribe(User $user, SubscriptionPlan $plan, ?int $invoiceId = null): Subscription
    {
        $subscription = DB::transaction(function () use ($user, $plan, $invoiceId) {
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

            $sub = Subscription::create([
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

            $this->log($sub, $action, $user->id, 'user', [
                'plan'  => $plan->name,
                'price' => $plan->price,
            ]);

            $this->notifySubscribed($user, $plan, $sub);
            $this->createActivationTicket($user, $plan, $sub);

            return $sub;
        });

        // کسب امتیاز بعد از فعال‌سازی (خارج از transaction)
        try {
            app(\App\Services\MembershipPointsService::class)
                ->earnFromSubscription($user, $subscription);
        } catch (\Throwable $e) {
            Log::warning('SubscriptionService: points earn failed on subscribe', ['error' => $e->getMessage()]);
        }

        // پاداش referral هنگام اولین اشتراک
        try {
            app(\App\Services\ReferralService::class)->rewardReferral($user);
        } catch (\Throwable $e) {
            Log::warning('SubscriptionService: referral reward failed', ['error' => $e->getMessage()]);
        }

        return $subscription;
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
        $this->invalidateGroupMemberCaches($subscription);

        return true;
    }

    /**
     * Renew an active/expired subscription.
     */
    public function renew(Subscription $subscription, ?int $invoiceId = null): Subscription
    {
        $renewed = DB::transaction(function () use ($subscription, $invoiceId) {
            $plan     = $subscription->plan;
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

        // کسب امتیاز بعد از تمدید (خارج از transaction)
        try {
            app(\App\Services\MembershipPointsService::class)
                ->earnFromRenewal($renewed->user, $renewed);
        } catch (\Throwable $e) {
            Log::warning('SubscriptionService: points earn failed on renew', ['error' => $e->getMessage()]);
        }

        return $renewed;
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
        $this->invalidateGroupMemberCaches($subscription);

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
     * Get user's active plan — checks own subscription first, then group membership.
     */
    public function getActivePlan(User $user): ?SubscriptionPlan
    {
        $ownPlan = $this->getActive($user)?->plan;
        if ($ownPlan) return $ownPlan;

        // group membership fallback
        try {
            $groupSub = app(\App\Services\GroupSubscriptionService::class)
                ->getActiveMemberSubscription($user);
            return $groupSub?->plan;
        } catch (\Throwable $e) {
            Log::warning('SubscriptionService: group plan lookup failed', ['error' => $e->getMessage()]);
            return null;
        }
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
     * محاسبه اعتبار باقیمانده اشتراک بر اساس روزهای مانده (prorated).
     * اشتراک‌های رایگان یا Trial کردیتی ندارند.
     */
    public function calculateCredit(Subscription $subscription): float
    {
        if ($subscription->status !== Subscription::STATUS_ACTIVE) {
            return 0.0;
        }

        $plan = $subscription->plan;
        if (! $plan || $plan->isFree()) {
            return 0.0;
        }

        $daysRemaining = max(0, (int) now()->diffInDays($subscription->ends_at, false));
        $totalDays     = $plan->getDurationInDays();

        if ($totalDays <= 0 || $daysRemaining <= 0) {
            return 0.0;
        }

        return round(($daysRemaining / $totalDays) * $plan->getEffectivePrice());
    }

    /**
     * پیش‌نمایش هزینه ارتقاء/تنزل پلن — بدون هیچ تغییر DB.
     */
    public function upgradePreview(Subscription $current, SubscriptionPlan $newPlan): array
    {
        $credit   = $this->calculateCredit($current);
        $newPrice = $newPlan->getEffectivePrice();
        $toPay    = max(0.0, $newPrice - $credit);
        $leftover = max(0.0, $credit - $newPrice);

        $currentPrice = $current->plan?->getEffectivePrice() ?? 0.0;

        return [
            'credit'          => $credit,
            'new_price'       => $newPrice,
            'amount_to_pay'   => $toPay,
            'leftover_credit' => $leftover,
            'leftover_points' => (int) floor($leftover / \App\Services\MembershipPointsService::EARN_PER_TOMAN),
            'is_free'         => $newPlan->isFree(),
            'is_direct'       => ($toPay == 0.0),
            'is_same'         => ($current->subscription_plan_id === $newPlan->id),
            'direction'       => $newPrice >= $currentPrice ? 'upgrade' : 'downgrade',
        ];
    }

    /**
     * ارتقاء/تنزل پلن — فرض: پرداخت قبلاً انجام شده یا amount_to_pay = 0.
     * اشتراک قدیمی cancel + اشتراک جدید active می‌شود.
     */
    public function upgrade(
        User $user,
        Subscription $current,
        SubscriptionPlan $newPlan,
        ?int $invoiceId = null,
        float $creditUsed = 0.0
    ): Subscription {
        $subscription = DB::transaction(function () use ($user, $current, $newPlan, $invoiceId, $creditUsed) {
            $current->update([
                'status'              => Subscription::STATUS_CANCELLED,
                'cancelled_at'        => now(),
                'cancellation_reason' => 'تغییر پلن به «' . $newPlan->name . '»',
            ]);
            $this->log($current, SubscriptionLog::ACTION_CANCELLED, $user->id, 'system', [
                'reason' => 'plan_change',
            ]);

            $now = now();
            $sub = Subscription::create([
                'user_id'                      => $user->id,
                'subscription_plan_id'         => $newPlan->id,
                'status'                       => Subscription::STATUS_ACTIVE,
                'starts_at'                    => $now,
                'ends_at'                      => $now->copy()->addDays($newPlan->getDurationInDays()),
                'invoice_id'                   => $invoiceId,
                'upgrade_from_subscription_id' => $current->id,
                'credit_amount'                => (int) round($creditUsed),
            ]);

            $currentPrice = $current->plan?->getEffectivePrice() ?? 0.0;
            $action = $newPlan->getEffectivePrice() >= $currentPrice
                ? SubscriptionLog::ACTION_UPGRADED
                : SubscriptionLog::ACTION_DOWNGRADED;

            $this->log($sub, $action, $user->id, 'user', [
                'from_plan'   => $current->plan?->name,
                'to_plan'     => $newPlan->name,
                'credit_used' => (int) round($creditUsed),
            ]);

            return $sub;
        });

        $this->notifyPlanChanged($user, $current->plan, $newPlan, $subscription);
        $this->invalidateGroupMemberCaches($current);

        // اعتبار مازاد (وقتی به پلن ارزان‌تر تغییر داده) → امتیاز وفاداری
        $leftover       = max(0.0, $creditUsed - $newPlan->getEffectivePrice());
        $leftoverPoints = (int) floor($leftover / \App\Services\MembershipPointsService::EARN_PER_TOMAN);
        if ($leftoverPoints > 0) {
            try {
                app(\App\Services\MembershipPointsService::class)
                    ->earnFromPlanChange($user, $subscription, $leftoverPoints);
            } catch (\Throwable $e) {
                Log::warning('SubscriptionService: credit-to-points failed on plan change', ['error' => $e->getMessage()]);
            }
        }

        return $subscription;
    }

    /**
     * ساخت فاکتور ارتقاء با اعتبار اعمال‌شده به عنوان تخفیف.
     */
    public function createUpgradeInvoice(
        User $user,
        SubscriptionPlan $newPlan,
        Subscription $current,
        int $creditAmount
    ): \App\Models\Invoice {
        $number = 'SUB-' . now()->format('Ymd') . '-' . str_pad(
            \App\Models\Invoice::whereDate('created_at', today())->count() + 1,
            4, '0', STR_PAD_LEFT
        );

        $basePrice  = (int) $newPlan->getEffectivePrice();
        $discount   = min($creditAmount, $basePrice);
        $finalTotal = max(0, $basePrice - $discount);

        $fromName = $current->plan?->name ?? '—';
        $invoice  = \App\Models\Invoice::create([
            'invoice_number'  => $number,
            'user_id'         => $user->id,
            'customer_name'   => $user->name ?? $user->mobile,
            'customer_mobile' => $user->mobile,
            'subtotal'        => $basePrice,
            'discount'        => $discount,
            'total'           => $finalTotal,
            'status'          => 'pending',
            'notes'           => "تغییر پلن از «{$fromName}» به «{$newPlan->name}» | اعتبار: {$discount} تومان",
        ]);

        \App\Models\InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'title'      => "تغییر پلن از «{$fromName}» به «{$newPlan->name}» ({$newPlan->getDurationLabel()})",
            'price'      => $basePrice,
            'quantity'   => 1,
            'total'      => $finalTotal,
        ]);

        return $invoice;
    }

    /**
     * Create a subscription invoice in the invoices table.
     * $discountAmount: مقدار تخفیف کوپن (اختیاری)
     * $coupon: مدل کوپن برای ذخیره در یادداشت فاکتور (اختیاری)
     */
    public function createInvoice(
        User $user,
        SubscriptionPlan $plan,
        int $discountAmount = 0,
        ?\App\Models\Coupon $coupon = null
    ): \App\Models\Invoice {
        $number = 'SUB-' . now()->format('Ymd') . '-' . str_pad(
            \App\Models\Invoice::whereDate('created_at', today())->count() + 1,
            4, '0', STR_PAD_LEFT
        );

        $basePrice      = (int) $plan->getEffectivePrice();
        $discountAmount = min($discountAmount, $basePrice);
        $finalTotal     = max(0, $basePrice - $discountAmount);

        $invoice = \App\Models\Invoice::create([
            'invoice_number'   => $number,
            'user_id'          => $user->id,
            'customer_name'    => $user->name ?? $user->mobile,
            'customer_mobile'  => $user->mobile,
            'subtotal'         => $basePrice,
            'discount'         => $discountAmount,
            'total'            => $finalTotal,
            'status'           => 'pending',
            'notes'            => $coupon ? 'کد تخفیف: ' . $coupon->code : null,
        ]);

        $itemTitle = 'اشتراک پلن ' . $plan->name . ' (' . $plan->getDurationLabel() . ')';
        if ($coupon && $discountAmount > 0) {
            $itemTitle .= ' — کد تخفیف: ' . $coupon->code;
        }

        \App\Models\InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'title'      => $itemTitle,
            'price'      => $basePrice,
            'quantity'   => 1,
            'total'      => $finalTotal,
        ]);

        return $invoice;
    }

    /**
     * ارسال notification (SMS + تیکت) پس از فعال‌سازی توسط ادمین — بدون ساخت subscription جدید.
     */
    public function notifyActivation(User $user, SubscriptionPlan $plan, Subscription $subscription): void
    {
        $this->notifySubscribed($user, $plan, $subscription);
        $this->createActivationTicket($user, $plan, $subscription);
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

        $webhookEvent = self::WEBHOOK_EVENT_MAP[$action] ?? null;
        if ($webhookEvent) {
            try {
                $subscription->loadMissing(['plan', 'user']);
                $this->webhooks->dispatch($webhookEvent, [
                    'subscription_id' => $subscription->id,
                    'user_id'         => $userId,
                    'user_name'       => $subscription->user?->name,
                    'plan'            => $subscription->plan?->name,
                    'plan_slug'       => $subscription->plan?->slug,
                    'status'          => $subscription->status,
                    'starts_at'       => $subscription->starts_at?->toIso8601String(),
                    'ends_at'         => $subscription->ends_at?->toIso8601String(),
                    'action'          => $action,
                    'details'         => $details,
                ]);
            } catch (\Throwable $e) {
                Log::warning("Webhook dispatch skipped for action '{$action}': {$e->getMessage()}");
            }
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

    /**
     * وقتی اشتراکی expire یا cancel می‌شود، کش benefit کلیه اعضای گروه را flush کن.
     * بدون این، اعضا تا TTL کش (۵ دقیقه) همچنان مزایا می‌گیرند.
     */
    public function invalidateGroupMemberCaches(Subscription $subscription): void
    {
        try {
            $benefitSvc = app(\App\Services\MembershipBenefitService::class);

            \App\Models\SubscriptionMember::where('subscription_id', $subscription->id)
                ->where('status', \App\Models\SubscriptionMember::STATUS_ACTIVE)
                ->with('user')
                ->get()
                ->each(fn ($m) => $benefitSvc->invalidateCache($m->user));
        } catch (\Throwable $e) {
            Log::warning('SubscriptionService: group member cache flush failed', [
                'subscription_id' => $subscription->id,
                'error'           => $e->getMessage(),
            ]);
        }
    }

    private function notifyPlanChanged(User $user, ?SubscriptionPlan $from, SubscriptionPlan $to, Subscription $sub): void
    {
        if (! $user->mobile) return;
        $ends = \App\Helpers\Jalali::formatFa($sub->ends_at, 'Y/m/d');
        $this->sms->send(
            $user->mobile,
            "سلام {$user->name}\nپلن اشتراک شما به «{$to->name}» تغییر یافت.\nتاریخ انقضا: {$ends}"
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

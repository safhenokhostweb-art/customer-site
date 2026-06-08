<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionMember;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GroupSubscriptionService
{
    public function __construct(private SmsService $sms) {}

    /**
     * تعداد صندلی‌های در دسترس برای دعوت (owner را شامل نمی‌شود).
     */
    public function availableSeats(Subscription $sub): int
    {
        $maxUsers = (int) ($sub->plan->max_users ?? 0);
        if ($maxUsers <= 1) return 0;

        $occupied = SubscriptionMember::where('subscription_id', $sub->id)
            ->whereIn('status', [SubscriptionMember::STATUS_ACTIVE, SubscriptionMember::STATUS_PENDING])
            ->count();

        return max(0, $maxUsers - 1 - $occupied);
    }

    /**
     * دعوت کاربر با شماره موبایل.
     *
     * @throws \RuntimeException اگر پلن group نباشد، ظرفیت پر باشد، یا کاربر قبلاً دعوت شده
     */
    public function invite(Subscription $sub, User $owner, string $mobile): SubscriptionMember
    {
        if (! $this->isGroupPlan($sub)) {
            throw new \RuntimeException('این پلن قابلیت اشتراک گروهی ندارد.');
        }

        if ($this->availableSeats($sub) <= 0) {
            throw new \RuntimeException('ظرفیت اشتراک گروهی پر شده است.');
        }

        $invitee = User::where('mobile', $mobile)->first();
        if (! $invitee) {
            throw new \RuntimeException("کاربری با شماره «{$mobile}» در سیستم ثبت نشده است.");
        }

        if ($invitee->id === $owner->id) {
            throw new \RuntimeException('نمی‌توانید خودتان را دعوت کنید.');
        }

        $existing = SubscriptionMember::where('subscription_id', $sub->id)
            ->where('user_id', $invitee->id)
            ->first();

        if ($existing) {
            $statusLabel = $existing->getStatusLabel();
            throw new \RuntimeException("این کاربر قبلاً دعوت شده است (وضعیت: {$statusLabel}).");
        }

        // بررسی: آیا کاربر عضو گروه دیگری است؟
        if ($this->hasActiveMembership($invitee)) {
            throw new \RuntimeException('این کاربر در حال حاضر عضو اشتراک گروهی دیگری است.');
        }

        $member = DB::transaction(function () use ($sub, $owner, $invitee) {
            return SubscriptionMember::create([
                'subscription_id' => $sub->id,
                'user_id'         => $invitee->id,
                'invited_by'      => $owner->id,
                'status'          => SubscriptionMember::STATUS_PENDING,
                'invited_at'      => now(),
            ]);
        });

        $this->notifyInvite($invitee, $owner, $sub);

        return $member;
    }

    /**
     * پذیرش دعوت‌نامه توسط کاربر دعوت‌شده.
     *
     * @throws \RuntimeException
     */
    public function accept(SubscriptionMember $member, User $user): bool
    {
        if ($member->user_id !== $user->id) {
            throw new \RuntimeException('دسترسی غیرمجاز.');
        }

        if (! $member->isPending()) {
            throw new \RuntimeException('این دعوت‌نامه دیگر قابل پذیرش نیست.');
        }

        // بررسی: اشتراک هنوز فعال است؟
        $sub = $member->subscription;
        if (! $sub || ! $sub->isActive()) {
            throw new \RuntimeException('اشتراک مورد نظر فعال نیست.');
        }

        // بررسی: ظرفیت
        if ($this->availableSeats($sub) <= 0) {
            throw new \RuntimeException('ظرفیت اشتراک گروهی پر شده است.');
        }

        $member->update([
            'status'    => SubscriptionMember::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);

        $this->invalidateMemberCache($user);
        $this->notifyAccepted($member->inviter, $user, $sub);

        return true;
    }

    /**
     * رد دعوت‌نامه توسط کاربر دعوت‌شده.
     */
    public function reject(SubscriptionMember $member, User $user): bool
    {
        if ($member->user_id !== $user->id) {
            throw new \RuntimeException('دسترسی غیرمجاز.');
        }

        if (! $member->isPending()) {
            return false;
        }

        $member->update(['status' => SubscriptionMember::STATUS_REJECTED]);
        return true;
    }

    /**
     * حذف عضو توسط صاحب اشتراک.
     */
    public function remove(SubscriptionMember $member, User $owner): bool
    {
        if ($member->subscription->user_id !== $owner->id) {
            throw new \RuntimeException('دسترسی غیرمجاز.');
        }

        if (! in_array($member->status, [SubscriptionMember::STATUS_ACTIVE, SubscriptionMember::STATUS_PENDING])) {
            return false;
        }

        $member->update([
            'status'     => SubscriptionMember::STATUS_REMOVED,
            'removed_at' => now(),
        ]);

        $this->invalidateMemberCache($member->user);
        $this->notifyRemoved($member->user, $member->subscription);

        return true;
    }

    /**
     * آیا پلن اشتراک group subscription را پشتیبانی می‌کند؟
     */
    public function isGroupPlan(Subscription $sub): bool
    {
        return (int) ($sub->plan?->max_users ?? 0) > 1;
    }

    /**
     * آیا کاربر عضو فعال یک اشتراک گروهی دیگری است؟
     */
    public function hasActiveMembership(User $user): bool
    {
        return SubscriptionMember::where('user_id', $user->id)
            ->where('status', SubscriptionMember::STATUS_ACTIVE)
            ->whereHas('subscription', fn ($q) => $q
                ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIAL])
                ->where('ends_at', '>', now())
            )
            ->exists();
    }

    /**
     * دعوت‌نامه‌های در انتظار برای یک کاربر.
     */
    public function pendingInvites(User $user): Collection
    {
        return SubscriptionMember::where('user_id', $user->id)
            ->where('status', SubscriptionMember::STATUS_PENDING)
            ->whereHas('subscription', fn ($q) => $q
                ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIAL])
                ->where('ends_at', '>', now())
            )
            ->with(['subscription.plan', 'inviter'])
            ->get();
    }

    /**
     * اشتراک گروهی که کاربر در آن عضو فعال است (جهت نمایش مزایا).
     */
    public function getActiveMemberSubscription(User $user): ?Subscription
    {
        $m = SubscriptionMember::where('user_id', $user->id)
            ->where('status', SubscriptionMember::STATUS_ACTIVE)
            ->whereHas('subscription', fn ($q) => $q
                ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIAL])
                ->where('ends_at', '>', now())
            )
            ->with('subscription.plan.products')
            ->first();

        return $m?->subscription;
    }

    // ─── Private ──────────────────────────────────────────────────────────

    private function invalidateMemberCache(User $user): void
    {
        try {
            app(\App\Services\MembershipBenefitService::class)->invalidateCache($user);
        } catch (\Throwable) {}
    }

    private function notifyInvite(User $invitee, User $owner, Subscription $sub): void
    {
        if (! $invitee->mobile) return;
        try {
            $this->sms->send(
                $invitee->mobile,
                "سلام {$invitee->name}\n{$owner->name} شما را به اشتراک گروهی «{$sub->plan?->name}» دعوت کرده است.\nبرای پذیرش وارد پنل کاربری شوید."
            );
        } catch (\Throwable $e) {
            Log::warning('GroupSubscriptionService: invite SMS failed', ['error' => $e->getMessage()]);
        }
    }

    private function notifyAccepted(User $owner, User $member, Subscription $sub): void
    {
        if (! $owner?->mobile) return;
        try {
            $this->sms->send(
                $owner->mobile,
                "{$member->name} دعوت شما به اشتراک «{$sub->plan?->name}» را پذیرفت."
            );
        } catch (\Throwable $e) {
            Log::warning('GroupSubscriptionService: accept SMS failed', ['error' => $e->getMessage()]);
        }
    }

    private function notifyRemoved(User $member, Subscription $sub): void
    {
        if (! $member?->mobile) return;
        try {
            $this->sms->send(
                $member->mobile,
                "عضویت شما در اشتراک گروهی «{$sub->plan?->name}» توسط مدیر اشتراک حذف شد."
            );
        } catch (\Throwable $e) {
            Log::warning('GroupSubscriptionService: remove SMS failed', ['error' => $e->getMessage()]);
        }
    }
}

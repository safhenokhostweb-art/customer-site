<?php

namespace App\Services;

use App\Models\MembershipPoint;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MembershipPointsService
{
    // ── ثوابت پیکربندی ──────────────────────────────────────────────────────
    public const EARN_PER_TOMAN       = 1000;   // هر 1000 تومان = 1 امتیاز پایه
    public const TOMAN_PER_POINT      = 10;     // هر 1000 امتیاز = 10,000 تومان
    public const MAX_SPEND_PERCENT    = 20;     // حداکثر 20% سفارش با امتیاز
    public const EXPIRY_DAYS          = 365;    // امتیازهای کسب‌شده ۱ سال اعتبار دارند
    public const POINTS_ON_SUBSCRIBE  = 500;    // امتیاز فعال‌سازی اشتراک
    public const POINTS_ON_RENEW      = 300;    // امتیاز تمدید اشتراک

    public function __construct(
        private readonly MembershipBenefitService $benefitService
    ) {}

    // ─── Balance ───────────────────────────────────────────────────────────

    public function getBalance(User $user): int
    {
        return (int) MembershipPoint::where('user_id', $user->id)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->sum('amount');
    }

    public function canSpend(User $user, int $points): bool
    {
        return $points > 0 && $this->getBalance($user) >= $points;
    }

    // ─── Earn ──────────────────────────────────────────────────────────────

    /**
     * محاسبه امتیاز قابل کسب از خرید (بدون ذخیره).
     */
    public function calculatePurchasePoints(User $user, int $orderTotal): int
    {
        $plan = $this->benefitService->getActivePlan($user);
        if (! $plan) {
            return 0;
        }

        $multiplier = max(0.0, (float) ($plan->loyalty_multiplier ?? 1.0));
        if ($multiplier <= 0) {
            return 0;
        }

        $base = (int) floor($orderTotal / self::EARN_PER_TOMAN);
        return (int) round($base * $multiplier);
    }

    /**
     * کسب امتیاز بعد از ثبت سفارش.
     */
    public function earnFromPurchase(User $user, Order $order): ?MembershipPoint
    {
        $points = $this->calculatePurchasePoints($user, (int) $order->subtotal);
        if ($points <= 0) {
            return null;
        }

        return $this->earn(
            $user,
            $points,
            MembershipPoint::TYPE_EARN,
            MembershipPoint::SOURCE_PURCHASE,
            "خرید سفارش #{$order->number}",
            $order->id,
            Order::class
        );
    }

    /**
     * کسب امتیاز بعد از فعال‌سازی اشتراک.
     */
    public function earnFromSubscription(User $user, Subscription $subscription): ?MembershipPoint
    {
        return $this->earn(
            $user,
            self::POINTS_ON_SUBSCRIBE,
            MembershipPoint::TYPE_EARN,
            MembershipPoint::SOURCE_SUBSCRIBE,
            "فعال‌سازی اشتراک «{$subscription->plan?->name}»",
            $subscription->id,
            Subscription::class
        );
    }

    /**
     * کسب امتیاز بعد از تمدید اشتراک.
     */
    public function earnFromRenewal(User $user, Subscription $subscription): ?MembershipPoint
    {
        return $this->earn(
            $user,
            self::POINTS_ON_RENEW,
            MembershipPoint::TYPE_EARN,
            MembershipPoint::SOURCE_RENEW,
            "تمدید اشتراک «{$subscription->plan?->name}»",
            $subscription->id,
            Subscription::class
        );
    }

    /**
     * کسب امتیاز از اعتبار باقیمانده هنگام تغییر به پلن ارزان‌تر.
     */
    public function earnFromPlanChange(User $user, Subscription $subscription, int $points): ?MembershipPoint
    {
        if ($points <= 0) return null;

        return $this->earn(
            $user,
            $points,
            MembershipPoint::TYPE_EARN,
            MembershipPoint::SOURCE_PLAN_CHANGE,
            "اعتبار باقیمانده از تغییر پلن به «{$subscription->plan?->name}»",
            $subscription->id,
            Subscription::class
        );
    }

    /**
     * کسب امتیاز برای معرفی کاربر جدید (هر دو طرف).
     */
    public function earnFromReferral(User $user, int $points, User $otherUser): ?MembershipPoint
    {
        return $this->earn(
            $user,
            $points,
            MembershipPoint::TYPE_EARN,
            MembershipPoint::SOURCE_REFERRAL,
            "معرفی کاربر «{$otherUser->name}»",
            $otherUser->id,
            User::class
        );
    }

    // ─── Spend ─────────────────────────────────────────────────────────────

    /**
     * محاسبه مبلغ تومانی معادل امتیازها (بدون ذخیره).
     */
    public function pointsToToman(int $points): int
    {
        return $points * self::TOMAN_PER_POINT;
    }

    /**
     * محاسبه حداکثر امتیاز قابل مصرف برای یک سفارش.
     * max = MIN(موجودی کاربر, 20% مبلغ سفارش / ارزش هر امتیاز)
     */
    public function maxSpendablePoints(User $user, int $orderTotal): int
    {
        $balance = $this->getBalance($user);
        if ($balance <= 0 || $orderTotal <= 0) {
            return 0;
        }

        $maxByOrder = (int) floor(($orderTotal * self::MAX_SPEND_PERCENT / 100) / self::TOMAN_PER_POINT);
        return min($balance, $maxByOrder);
    }

    /**
     * مصرف امتیاز در checkout (درون DB::transaction فراخوانی می‌شود).
     *
     * @throws \RuntimeException اگر موجودی کافی نباشد
     */
    public function spendAtCheckout(User $user, int $points, Order $order): MembershipPoint
    {
        if (! $this->canSpend($user, $points)) {
            throw new \RuntimeException('موجودی امتیاز کافی نیست.');
        }

        return $this->record(
            $user,
            -$points,
            MembershipPoint::TYPE_SPEND,
            MembershipPoint::SOURCE_CHECKOUT_SPEND,
            "مصرف در سفارش #{$order->number}",
            $order->id,
            Order::class
        );
    }

    // ─── History ───────────────────────────────────────────────────────────

    public function getHistory(User $user, int $perPage = 20): \Illuminate\Pagination\LengthAwarePaginator
    {
        return MembershipPoint::where('user_id', $user->id)
            ->latest()
            ->paginate($perPage);
    }

    // ─── Private ───────────────────────────────────────────────────────────

    private function earn(
        User   $user,
        int    $points,
        string $type,
        string $source,
        string $description,
        ?int   $referenceId   = null,
        ?string $referenceType = null
    ): MembershipPoint {
        return $this->record(
            $user, $points, $type, $source, $description,
            $referenceId, $referenceType,
            now()->addDays(self::EXPIRY_DAYS)
        );
    }

    private function record(
        User    $user,
        int     $amount,
        string  $type,
        string  $source,
        string  $description,
        ?int    $referenceId   = null,
        ?string $referenceType = null,
        ?object $expiresAt     = null
    ): MembershipPoint {
        try {
            $balance = $this->getBalance($user) + $amount;
            $balance = max(0, $balance);

            return MembershipPoint::create([
                'user_id'        => $user->id,
                'amount'         => $amount,
                'balance_after'  => $balance,
                'type'           => $type,
                'source'         => $source,
                'description'    => $description,
                'reference_id'   => $referenceId,
                'reference_type' => $referenceType,
                'expires_at'     => $expiresAt,
            ]);
        } catch (\Throwable $e) {
            Log::error('MembershipPointsService::record failed', [
                'user_id' => $user->id,
                'amount'  => $amount,
                'source'  => $source,
                'error'   => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

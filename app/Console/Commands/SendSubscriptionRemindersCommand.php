<?php

namespace App\Console\Commands;

use App\Helpers\Jalali;
use App\Helpers\NumberHelper;
use App\Models\Coupon;
use App\Models\Subscription;
use App\Models\SubscriptionLog;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendSubscriptionRemindersCommand extends Command
{
    protected $signature = 'subscriptions:send-reminders
                            {--dry-run : فقط تعداد را نمایش بده بدون ارسال}';

    protected $description = 'ارسال SMS یادآوری انقضای اشتراک (۷ روز و ۱ روز قبل) + کوپن تمدید';

    private const RENEWAL_DISCOUNT_PERCENT = 10;
    private const RENEWAL_COUPON_HOURS     = 48;
    private const REMINDER_WINDOW_HOURS    = 12; // ساعت تلرانس برای query

    public function handle(SmsService $sms): int
    {
        $dryRun = $this->option('dry-run');

        [$sent7d, $sent1d] = $this->processReminders($sms, $dryRun);

        if ($dryRun) {
            $this->info("dry-run | قابل ارسال: {$sent7d} یادآوری ۷روزه، {$sent1d} یادآوری ۱روزه");
        } else {
            $this->info("✓ {$sent7d} یادآوری ۷روزه و {$sent1d} یادآوری ۱روزه ارسال شد.");
        }

        return self::SUCCESS;
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function processReminders(SmsService $sms, bool $dryRun): array
    {
        $window = self::REMINDER_WINDOW_HOURS;

        // ── ۷-روزه ──────────────────────────────────────────────────────────
        $target7d = now()->addDays(7);
        $subs7d = Subscription::with(['user', 'plan'])
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIAL])
            ->whereBetween('ends_at', [
                $target7d->copy()->subHours($window),
                $target7d->copy()->addHours($window),
            ])
            ->whereNull('reminder_7d_sent_at')
            ->get();

        // ── ۱-روزه ──────────────────────────────────────────────────────────
        $target1d = now()->addDay();
        $subs1d = Subscription::with(['user', 'plan'])
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIAL])
            ->whereBetween('ends_at', [
                $target1d->copy()->subHours($window),
                $target1d->copy()->addHours($window),
            ])
            ->whereNull('reminder_1d_sent_at')
            ->get();

        if ($dryRun) {
            return [$subs7d->count(), $subs1d->count()];
        }

        $sent7d = 0;
        foreach ($subs7d as $sub) {
            try {
                $this->send7DayReminder($sub, $sms);
                $sent7d++;
            } catch (\Throwable $e) {
                Log::error('subscription reminder 7d failed', ['sub' => $sub->id, 'error' => $e->getMessage()]);
            }
        }

        $sent1d = 0;
        foreach ($subs1d as $sub) {
            try {
                $this->send1DayReminder($sub, $sms);
                $sent1d++;
            } catch (\Throwable $e) {
                Log::error('subscription reminder 1d failed', ['sub' => $sub->id, 'error' => $e->getMessage()]);
            }
        }

        return [$sent7d, $sent1d];
    }

    private function send7DayReminder(Subscription $sub, SmsService $sms): void
    {
        $user    = $sub->user;
        $plan    = $sub->plan;
        $endsAt  = Jalali::formatFa($sub->ends_at, 'Y/m/d');
        $daysLeft = NumberHelper::formatFa((int) now()->diffInDays($sub->ends_at, false));

        if ($user?->mobile) {
            $sms->send(
                $user->mobile,
                "سلام {$user->name}،\n" .
                "اشتراک «{$plan?->name}» شما تا {$daysLeft} روز دیگر ({$endsAt}) منقضی می‌شود.\n" .
                "همین حالا تمدید کنید و امتیاز وفاداری دریافت کنید."
            );
        }

        DB::transaction(function () use ($sub) {
            $sub->update(['reminder_7d_sent_at' => now()]);
            SubscriptionLog::create([
                'subscription_id' => $sub->id,
                'user_id'         => $sub->user_id,
                'action'          => SubscriptionLog::ACTION_REMINDER_7D,
                'performed_by'    => 'system',
                'details'         => ['sent_at' => now()->toDateTimeString()],
            ]);
        });
    }

    private function send1DayReminder(Subscription $sub, SmsService $sms): void
    {
        $user   = $sub->user;
        $plan   = $sub->plan;
        $endsAt = Jalali::formatFa($sub->ends_at, 'Y/m/d');
        $coupon = $this->generateRenewalCoupon($sub);

        if ($user?->mobile) {
            $msg = "سلام {$user->name}،\n" .
                   "فردا ({$endsAt}) اشتراک «{$plan?->name}» شما منقضی می‌شود!\n";

            if ($coupon) {
                $discount = NumberHelper::formatFa(self::RENEWAL_DISCOUNT_PERCENT);
                $msg .= "کد تخفیف ویژه تمدید: {$coupon->code}\n" .
                        "{$discount}٪ تخفیف — اعتبار ۴۸ ساعته.";
            } else {
                $msg .= "برای تمدید وارد پنل کاربری شوید.";
            }

            $sms->send($user->mobile, $msg);
        }

        DB::transaction(function () use ($sub, $coupon) {
            $sub->update(['reminder_1d_sent_at' => now()]);
            SubscriptionLog::create([
                'subscription_id' => $sub->id,
                'user_id'         => $sub->user_id,
                'action'          => SubscriptionLog::ACTION_REMINDER_1D,
                'performed_by'    => 'system',
                'details'         => [
                    'sent_at'     => now()->toDateTimeString(),
                    'coupon_code' => $coupon?->code,
                ],
            ]);
            if ($coupon) {
                SubscriptionLog::create([
                    'subscription_id' => $sub->id,
                    'user_id'         => $sub->user_id,
                    'action'          => SubscriptionLog::ACTION_RENEWAL_COUPON,
                    'performed_by'    => 'system',
                    'details'         => ['coupon_id' => $coupon->id, 'code' => $coupon->code],
                ]);
            }
        });
    }

    private function generateRenewalCoupon(Subscription $sub): ?Coupon
    {
        try {
            $code = 'RENEW' . strtoupper(substr(md5($sub->id . now()->toDateString()), 0, 6));

            // اگر قبلاً ساخته شده باشد، همان را برگردان
            $existing = Coupon::where('code', $code)->first();
            if ($existing) {
                return $existing;
            }

            return Coupon::create([
                'code'             => $code,
                'discount_type'    => Coupon::TYPE_PERCENT,
                'discount_percent' => self::RENEWAL_DISCOUNT_PERCENT,
                'discount_amount'  => null,
                'is_active'        => true,
                'usage_limit'      => 1,
                'used_count'       => 0,
                'expires_at'       => now()->addHours(self::RENEWAL_COUPON_HOURS),
                'first_order_only' => false,
            ]);
        } catch (\Throwable $e) {
            Log::warning('renewal coupon generation failed', ['sub' => $sub->id, 'error' => $e->getMessage()]);
            return null;
        }
    }
}

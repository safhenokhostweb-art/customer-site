<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\SubscriptionLog;
use App\Services\SmsService;
use App\Services\SubscriptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessRenewalsCommand extends Command
{
    protected $signature = 'subscriptions:process-renewals
                            {--dry-run : فقط تعداد را نمایش بده بدون اجرا}
                            {--window=24 : ساعت قبل از انقضا برای پردازش}';

    protected $description = 'تمدید خودکار اشتراک‌های دارای auto_renew=true — رایگان: مستقیم | پولی: فاکتور + SMS';

    public function handle(SubscriptionService $service, SmsService $sms): int
    {
        $dryRun = $this->option('dry-run');
        $window = (int) $this->option('window');

        $subs = $this->fetchDueSubscriptions($window);

        if ($subs->isEmpty()) {
            $this->info('هیچ اشتراکی برای تمدید خودکار یافت نشد.');
            return self::SUCCESS;
        }

        $renewedFree = 0;
        $invoicedPaid = 0;
        $skipped = 0;

        foreach ($subs as $sub) {
            try {
                if ($sub->plan?->isFree()) {
                    if ($dryRun) {
                        $this->line("[dry-run] رایگان: {$sub->user?->mobile} — {$sub->plan->name}");
                        $renewedFree++;
                        continue;
                    }
                    $this->renewFree($sub, $service);
                    $renewedFree++;
                } else {
                    if ($dryRun) {
                        $this->line("[dry-run] پولی: {$sub->user?->mobile} — {$sub->plan->name} — فاکتور ارسال می‌شود");
                        $invoicedPaid++;
                        continue;
                    }
                    $this->createRenewalInvoice($sub, $service, $sms);
                    $invoicedPaid++;
                }
            } catch (\Throwable $e) {
                $skipped++;
                Log::error('ProcessRenewals: failed', [
                    'subscription_id' => $sub->id,
                    'error'           => $e->getMessage(),
                ]);
                $this->warn("خطا در پردازش اشتراک #{$sub->id}: " . $e->getMessage());
            }
        }

        $prefix = $dryRun ? '[dry-run] ' : '✓ ';
        $this->info("{$prefix}{$renewedFree} تمدید رایگان | {$invoicedPaid} فاکتور پولی | {$skipped} خطا");

        return self::SUCCESS;
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    /**
     * اشتراک‌هایی که:
     * - auto_renew=true
     * - status=active یا trial
     * - ends_at در window ساعت آینده
     * - امروز هنوز تمدید نشده‌اند (idempotent)
     */
    private function fetchDueSubscriptions(int $windowHours)
    {
        return Subscription::with(['user', 'plan'])
            ->where('auto_renew', true)
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIAL])
            ->where('ends_at', '<=', now()->addHours($windowHours))
            ->where('ends_at', '>', now()->subHours(1)) // منقضی‌شده‌های قدیمی را skip کن
            ->where(function ($q) {
                // هنوز امروز تمدید نشده (idempotent guard)
                $q->whereNull('renewed_at')
                  ->orWhere('renewed_at', '<', today());
            })
            ->get();
    }

    /**
     * تمدید خودکار پلن رایگان — مستقیم بدون پرداخت.
     */
    private function renewFree(Subscription $sub, SubscriptionService $service): void
    {
        $renewed = $service->renew($sub);

        SubscriptionLog::create([
            'subscription_id' => $sub->id,
            'user_id'         => $sub->user_id,
            'action'          => SubscriptionLog::ACTION_AUTO_RENEWED,
            'performed_by'    => 'system',
            'details'         => [
                'plan'     => $sub->plan?->name,
                'new_ends' => $renewed->ends_at?->toDateString(),
                'method'   => 'auto_free',
            ],
        ]);

        Log::info('ProcessRenewals: free renewed', [
            'sub_id' => $sub->id,
            'user'   => $sub->user?->mobile,
            'plan'   => $sub->plan?->name,
        ]);
    }

    /**
     * برای پلن پولی: فاکتور تمدید ایجاد + SMS با لینک پرداخت.
     * کاربر باید خودش پرداخت کند — charge خودکار در ایران ممکن نیست.
     * idempotent: اگر فاکتور pending برای همین plan امروز وجود دارد، skip می‌کند.
     */
    private function createRenewalInvoice(Subscription $sub, SubscriptionService $service, SmsService $sms): void
    {
        $user = $sub->user;
        $plan = $sub->plan;

        if (! $user || ! $plan) {
            return;
        }

        // جلوگیری از صدور فاکتور تکراری (همان plan — امروز — pending)
        $alreadyInvoiced = \App\Models\Invoice::where('user_id', $user->id)
            ->where('status', 'pending')
            ->whereDate('created_at', today())
            ->whereHas('items', fn($q) => $q->where('title', 'like', "%{$plan->name}%"))
            ->exists();

        if ($alreadyInvoiced) {
            Log::info('ProcessRenewals: renewal invoice already exists today', [
                'sub_id' => $sub->id,
                'user'   => $user->mobile,
            ]);
            return;
        }

        $invoice = $service->createInvoice($user, $plan);

        SubscriptionLog::create([
            'subscription_id' => $sub->id,
            'user_id'         => $user->id,
            'action'          => SubscriptionLog::ACTION_AUTO_RENEW_INVOICE,
            'performed_by'    => 'system',
            'details'         => [
                'plan'           => $plan->name,
                'invoice_number' => $invoice->invoice_number,
                'amount'         => $invoice->total,
                'ends_at'        => $sub->ends_at?->toDateString(),
            ],
        ]);

        // ارسال SMS با لینک پرداخت
        $payUrl  = url(route('subscriptions.pay', $invoice->invoice_number, false));
        $message = "کاربر گرامی، اشتراک «{$plan->name}» شما رو به انقضاست.\n"
                 . "برای تمدید خودکار لطفاً پرداخت کنید:\n{$payUrl}";

        try {
            $sms->send($user->mobile, $message);
        } catch (\Throwable $e) {
            Log::warning('ProcessRenewals: SMS failed', ['user' => $user->mobile, 'error' => $e->getMessage()]);
        }

        Log::info('ProcessRenewals: renewal invoice created', [
            'sub_id'  => $sub->id,
            'user'    => $user->mobile,
            'invoice' => $invoice->invoice_number,
        ]);
    }
}

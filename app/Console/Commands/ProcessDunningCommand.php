<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\SubscriptionLog;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * GAP-H05 — Dunning for Failed Renewals
 *
 * Queries pending renewal invoices and sends escalating SMS reminders:
 *   Day 3  → first reminder
 *   Day 7  → urgent reminder
 *   Day 14 → final warning (platform lifecycle scheduler handles suspension)
 *
 * Idempotent: tracked via SubscriptionLog entries.
 */
class ProcessDunningCommand extends Command
{
    protected $signature   = 'subscriptions:process-dunning
                              {--dry-run : نمایش بدون ارسال}';
    protected $description = 'GAP-H05: ارسال یادآوری‌های SMS برای فاکتورهای تمدید پرداخت‌نشده (روز ۳، ۷، ۱۴)';

    public function handle(SmsService $sms): int
    {
        $dryRun = $this->option('dry-run');

        // Find all SubscriptionLog entries where renewal invoices were created
        $renewalLogs = SubscriptionLog::where('action', SubscriptionLog::ACTION_AUTO_RENEW_INVOICE)
            ->where('created_at', '>=', now()->subDays(20))
            ->with('subscription.user', 'subscription.plan')
            ->get();

        if ($renewalLogs->isEmpty()) {
            $this->info('هیچ فاکتور تمدیدی برای بررسی یافت نشد.');
            return self::SUCCESS;
        }

        $day3Sent  = 0;
        $day7Sent  = 0;
        $day14Sent = 0;
        $skipped   = 0;

        foreach ($renewalLogs as $log) {
            try {
                $invoiceNumber = $log->details['invoice_number'] ?? null;
                if (! $invoiceNumber) {
                    continue;
                }

                $invoice = Invoice::where('invoice_number', $invoiceNumber)
                    ->where('status', 'pending')
                    ->first();

                if (! $invoice) {
                    // Already paid or cancelled — skip
                    continue;
                }

                $subscriptionId = $log->subscription_id;
                $userId         = $log->user_id;
                $ageInDays      = (int) $log->created_at->diffInDays(now());
                $planName       = $log->details['plan'] ?? 'اشتراک';
                $user           = $log->subscription?->user;

                if (! $user?->mobile) {
                    continue;
                }

                $payUrl = url(route('subscriptions.pay', $invoiceNumber, false));

                // ── Day 14 — final warning ────────────────────────────────
                if ($ageInDays >= 14 && ! $this->dunningLogExists($subscriptionId, $userId, SubscriptionLog::ACTION_DUNNING_DAY14)) {
                    if ($dryRun) {
                        $this->line("[dry-run] روز۱۴ — {$user->mobile} — {$planName}");
                    } else {
                        $message = "هشدار نهایی: فاکتور تمدید «{$planName}» ۱۴ روز است پرداخت نشده.\n"
                                 . "در صورت عدم پرداخت سایت شما به‌زودی به حالت تعلیق درمی‌آید.\n"
                                 . "پرداخت: {$payUrl}";
                        $this->sendSms($sms, $user->mobile, $message, $subscriptionId, $userId, SubscriptionLog::ACTION_DUNNING_DAY14, $planName, $invoiceNumber);
                    }
                    $day14Sent++;
                    continue;
                }

                // ── Day 7 — urgent reminder ───────────────────────────────
                if ($ageInDays >= 7 && ! $this->dunningLogExists($subscriptionId, $userId, SubscriptionLog::ACTION_DUNNING_DAY7)) {
                    if ($dryRun) {
                        $this->line("[dry-run] روز۷ — {$user->mobile} — {$planName}");
                    } else {
                        $message = "یادآوری فوری: فاکتور تمدید «{$planName}» هنوز پرداخت نشده است.\n"
                                 . "برای جلوگیری از تعلیق سایت لطفاً پرداخت کنید:\n{$payUrl}";
                        $this->sendSms($sms, $user->mobile, $message, $subscriptionId, $userId, SubscriptionLog::ACTION_DUNNING_DAY7, $planName, $invoiceNumber);
                    }
                    $day7Sent++;
                    continue;
                }

                // ── Day 3 — first reminder ────────────────────────────────
                if ($ageInDays >= 3 && ! $this->dunningLogExists($subscriptionId, $userId, SubscriptionLog::ACTION_DUNNING_DAY3)) {
                    if ($dryRun) {
                        $this->line("[dry-run] روز۳ — {$user->mobile} — {$planName}");
                    } else {
                        $message = "یادآوری: فاکتور تمدید اشتراک «{$planName}» شما هنوز پرداخت نشده است.\n"
                                 . "برای تمدید لطفاً پرداخت کنید:\n{$payUrl}";
                        $this->sendSms($sms, $user->mobile, $message, $subscriptionId, $userId, SubscriptionLog::ACTION_DUNNING_DAY3, $planName, $invoiceNumber);
                    }
                    $day3Sent++;
                }
            } catch (\Throwable $e) {
                $skipped++;
                Log::warning('ProcessDunning: error processing log', [
                    'log_id' => $log->id,
                    'error'  => $e->getMessage(),
                ]);
            }
        }

        $prefix = $dryRun ? '[dry-run] ' : '';
        $this->info("{$prefix}روز۳: {$day3Sent} | روز۷: {$day7Sent} | روز۱۴: {$day14Sent} | خطا: {$skipped}");

        return self::SUCCESS;
    }

    private function dunningLogExists(int $subscriptionId, int $userId, string $action): bool
    {
        return SubscriptionLog::where('subscription_id', $subscriptionId)
            ->where('user_id', $userId)
            ->where('action', $action)
            ->exists();
    }

    private function sendSms(SmsService $sms, string $mobile, string $message, int $subscriptionId, int $userId, string $action, string $planName, string $invoiceNumber): void
    {
        try {
            $sms->send($mobile, $message);
        } catch (\Throwable $e) {
            Log::warning("ProcessDunning: SMS failed [{$action}]", ['mobile' => $mobile, 'error' => $e->getMessage()]);
        }

        SubscriptionLog::create([
            'subscription_id' => $subscriptionId,
            'user_id'         => $userId,
            'action'          => $action,
            'performed_by'    => 'system',
            'details'         => [
                'plan'           => $planName,
                'invoice_number' => $invoiceNumber,
            ],
        ]);

        Log::info("ProcessDunning: {$action} sent", [
            'subscription_id' => $subscriptionId,
            'invoice'         => $invoiceNumber,
        ]);
    }
}

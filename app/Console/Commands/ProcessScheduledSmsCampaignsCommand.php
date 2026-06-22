<?php

namespace App\Console\Commands;

use App\Models\SmsCampaign;
use App\Services\SmsCampaignService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * C03 — Process scheduled SMS campaigns.
 *
 * Finds all campaigns with status='scheduled' and scheduled_at <= now(),
 * then fires SmsCampaignService::send() for each.
 *
 * Scheduled: every 5 minutes via bootstrap/app.php
 * Idempotent: status transitions from 'scheduled' → 'running' prevents double-fire.
 */
class ProcessScheduledSmsCampaignsCommand extends Command
{
    protected $signature = 'sms:process-scheduled
                            {--dry-run : نمایش کمپین‌ها بدون ارسال}';

    protected $description = 'C03 — پردازش و ارسال کمپین‌های SMS زمان‌بندی‌شده که وقتشان رسیده';

    public function handle(SmsCampaignService $service): int
    {
        $dryRun = $this->option('dry-run');

        // GAP-L04 — Recover campaigns stuck in "running" for more than 2 hours
        $this->recoverStuckCampaigns();

        $campaigns = SmsCampaign::where('status', SmsCampaign::STATUS_SCHEDULED)
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($campaigns->isEmpty()) {
            if ($this->getOutput()->isVerbose()) {
                $this->info('هیچ کمپینی برای پردازش یافت نشد.');
            }
            return self::SUCCESS;
        }

        $this->info("تعداد کمپین‌های آماده ارسال: {$campaigns->count()}");

        $sent    = 0;
        $skipped = 0;
        $failed  = 0;

        foreach ($campaigns as $campaign) {
            $campaignId = $campaign->id;

            if ($dryRun) {
                $this->line("[dry-run] کمپین #{$campaignId} — {$campaign->name} — {$campaign->scheduled_at}");
                $sent++;
                continue;
            }

            try {
                // Transactional lock to prevent double-dispatch in concurrent runs
                $claimed = DB::transaction(function () use ($campaignId) {
                    $locked = SmsCampaign::lockForUpdate()->find($campaignId);
                    if (! $locked || $locked->status !== SmsCampaign::STATUS_SCHEDULED) {
                        return null;
                    }
                    // Reserve it immediately to prevent another process picking it up
                    $locked->update(['status' => SmsCampaign::STATUS_RUNNING, 'started_at' => now()]);
                    return $locked;
                });

                if (! $claimed) {
                    $skipped++;
                    continue;
                }

                // Status is already RUNNING — call SmsCampaignService directly
                // which will build recipients and dispatch job
                $service->sendBatch($claimed);

                // For large campaigns (>50), dispatch remaining via job
                if ($claimed->total_recipients > 50) {
                    $claimed->pendingRecipients()
                        ->select('id')
                        ->chunkById(50, function ($chunk) use ($claimed) {
                            \App\Jobs\SendSmsCampaignJob::dispatch($claimed->id, $chunk->pluck('id')->toArray());
                        });
                }

                $sent++;

                Log::info('ProcessScheduledSmsCampaigns: campaign dispatched', [
                    'campaign_id' => $claimed->id,
                    'name'        => $claimed->name,
                    'scheduled'   => $claimed->scheduled_at?->toDateTimeString(),
                    'recipients'  => $claimed->total_recipients,
                ]);

                $this->line("✓ پردازش شد: [{$claimed->id}] {$claimed->name}");
            } catch (\Throwable $e) {
                $failed++;

                // Mark as failed so it doesn't block future runs
                try {
                    SmsCampaign::where('id', $campaignId)
                        ->where('status', SmsCampaign::STATUS_RUNNING)
                        ->update(['status' => SmsCampaign::STATUS_FAILED]);
                } catch (\Throwable) {}

                Log::error('ProcessScheduledSmsCampaigns: campaign failed', [
                    'campaign_id' => $campaignId,
                    'error'       => $e->getMessage(),
                ]);

                $this->warn("✗ خطا [{$campaignId}]: " . $e->getMessage());
            }
        }

        $prefix = $dryRun ? '[dry-run] ' : '';
        $this->info("{$prefix}ارسال شد: {$sent} | skip: {$skipped} | خطا: {$failed}");

        return self::SUCCESS;
    }

    /**
     * GAP-L04 — Reset campaigns stuck in "running" for more than 2 hours back to "failed".
     * This handles the case where a job died without completing or marking the campaign done.
     */
    private function recoverStuckCampaigns(): void
    {
        $stuckCutoff = now()->subHours(2);

        $stuck = SmsCampaign::where('status', SmsCampaign::STATUS_RUNNING)
            ->where('started_at', '<', $stuckCutoff)
            ->get();

        foreach ($stuck as $campaign) {
            // If all recipients were actually sent, mark complete — otherwise fail
            $totalSent = $campaign->recipients()->where('status', 'sent')->count();
            $newStatus  = ($totalSent >= $campaign->total_recipients && $campaign->total_recipients > 0)
                ? SmsCampaign::STATUS_COMPLETED
                : SmsCampaign::STATUS_FAILED;

            $campaign->update(['status' => $newStatus, 'completed_at' => now()]);

            Log::warning("ProcessScheduledSmsCampaigns: recovered stuck campaign [{$campaign->id}] → {$newStatus}", [
                'name'       => $campaign->name,
                'started_at' => $campaign->started_at?->toDateTimeString(),
                'total_sent' => $totalSent,
            ]);

            $this->warn("⟳ بازیابی کمپین گیر کرده [{$campaign->id}] {$campaign->name} → {$newStatus}");
        }
    }
}

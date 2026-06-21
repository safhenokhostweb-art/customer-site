<?php

namespace App\Jobs;

use App\Models\SmsCampaign;
use App\Models\SmsCampaignRecipient;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSmsCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 120;

    public function __construct(
        private int   $campaignId,
        private array $recipientIds
    ) {}

    public function handle(SmsService $sms): void
    {
        $campaign = SmsCampaign::find($this->campaignId);
        if (!$campaign || $campaign->status === SmsCampaign::STATUS_CANCELLED) {
            return;
        }

        $recipients = SmsCampaignRecipient::whereIn('id', $this->recipientIds)
            ->where('status', 'pending')
            ->get();

        $sentCount   = 0;
        $failedCount = 0;

        foreach ($recipients as $recipient) {
            $ok = $sms->send($recipient->mobile, $campaign->message);
            $recipient->update([
                'status'        => $ok ? 'sent' : 'failed',
                'sent_at'       => $ok ? now() : null,
                'error_message' => $ok ? null : 'خطا در ارسال',
            ]);
            $ok ? $sentCount++ : $failedCount++;
            usleep(50_000); // 50ms rate limit
        }

        // Atomic increment on the campaign counters
        $campaign->increment('sent_count', $sentCount);
        $campaign->increment('failed_count', $failedCount);

        // Check if all recipients have been processed
        $campaign->refresh();
        $pending = $campaign->recipients()->where('status', 'pending')->count();
        if ($pending === 0) {
            $campaign->update([
                'status'       => SmsCampaign::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
        }

        Log::info('SendSmsCampaignJob: batch done', [
            'campaign_id' => $this->campaignId,
            'sent'        => $sentCount,
            'failed'      => $failedCount,
            'pending'     => $pending,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        $campaign = SmsCampaign::find($this->campaignId);
        if ($campaign && $campaign->status === SmsCampaign::STATUS_RUNNING) {
            // Only mark failed if nothing was sent at all
            if ($campaign->sent_count === 0) {
                $campaign->update(['status' => SmsCampaign::STATUS_FAILED]);
            }
        }
        Log::error('SendSmsCampaignJob: failed', ['campaign_id' => $this->campaignId, 'error' => $e->getMessage()]);
    }
}

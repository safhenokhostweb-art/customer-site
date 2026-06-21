<?php

namespace App\Services;

use App\Models\SmsCampaign;
use App\Models\SmsCampaignRecipient;
use App\Models\CrmContact;
use App\Models\User;
use App\Models\Order;
use Illuminate\Support\Collection;

class SmsCampaignService
{
    public function __construct(private SmsService $sms) {}

    public function create(array $data, int $siteId, int $userId): SmsCampaign
    {
        return SmsCampaign::create([
            'site_id'         => $siteId,
            'name'            => $data['name'],
            'message'         => $data['message'],
            'audience_type'   => $data['audience_type'] ?? SmsCampaign::AUDIENCE_ALL,
            'audience_filter' => $data['audience_filter'] ?? null,
            'status'          => SmsCampaign::STATUS_DRAFT,
            'scheduled_at'    => $data['scheduled_at'] ?? null,
            'created_by_id'   => $userId,
        ]);
    }

    public function update(SmsCampaign $campaign, array $data): SmsCampaign
    {
        $campaign->update([
            'name'            => $data['name'],
            'message'         => $data['message'],
            'audience_type'   => $data['audience_type'],
            'audience_filter' => $data['audience_filter'] ?? null,
            'scheduled_at'    => $data['scheduled_at'] ?? null,
        ]);
        return $campaign->fresh();
    }

    /**
     * Build the recipient list from the campaign's audience type.
     * Returns count of recipients added.
     */
    public function buildRecipients(SmsCampaign $campaign): int
    {
        // Clear previous recipients if re-building
        $campaign->recipients()->delete();

        $mobiles = match ($campaign->audience_type) {
            SmsCampaign::AUDIENCE_ALL    => $this->allCustomerMobiles($campaign->site_id),
            SmsCampaign::AUDIENCE_CRM    => $this->crmMobiles($campaign),
            SmsCampaign::AUDIENCE_USERS  => $this->userMobiles($campaign->site_id),
            SmsCampaign::AUDIENCE_MANUAL => $this->parsedManualMobiles($campaign->audience_filter),
            default                      => collect(),
        };

        // Deduplicate
        $mobiles = $mobiles->unique('mobile')->values();

        // Bulk insert recipients
        $records = $mobiles->map(fn($m) => [
            'campaign_id' => $campaign->id,
            'mobile'      => $m['mobile'],
            'name'        => $m['name'] ?? null,
            'status'      => 'pending',
            'created_at'  => now(),
            'updated_at'  => now(),
        ])->all();

        foreach (array_chunk($records, 500) as $chunk) {
            SmsCampaignRecipient::insert($chunk);
        }

        $count = count($records);
        $campaign->update(['total_recipients' => $count]);
        return $count;
    }

    /**
     * Send campaign immediately (processes in this request; use job for large lists).
     * For campaigns with ≤ 50 recipients, sends inline.
     * For larger campaigns, dispatches SendSmsCampaignJob in batches.
     */
    public function send(SmsCampaign $campaign): void
    {
        $campaign->update([
            'status'     => SmsCampaign::STATUS_RUNNING,
            'started_at' => now(),
        ]);

        $total = $campaign->total_recipients;

        if ($total <= 50) {
            $this->sendBatch($campaign);
        } else {
            // Dispatch background job for each chunk of 50
            $campaign->pendingRecipients()
                ->select('id')
                ->chunkById(50, function ($chunk) use ($campaign) {
                    \App\Jobs\SendSmsCampaignJob::dispatch($campaign->id, $chunk->pluck('id')->toArray());
                });
        }
    }

    public function sendBatch(SmsCampaign $campaign): void
    {
        $pending = $campaign->pendingRecipients()->get();
        $sentCount   = $campaign->sent_count;
        $failedCount = $campaign->failed_count;

        foreach ($pending as $recipient) {
            $ok = $this->sms->send($recipient->mobile, $campaign->message);
            $recipient->update([
                'status'        => $ok ? 'sent' : 'failed',
                'sent_at'       => $ok ? now() : null,
                'error_message' => $ok ? null : 'خطا در ارسال',
            ]);
            $ok ? $sentCount++ : $failedCount++;
        }

        $allDone = $campaign->recipients()->where('status', 'pending')->doesntExist();
        $campaign->update([
            'sent_count'   => $sentCount,
            'failed_count' => $failedCount,
            'status'       => $allDone ? SmsCampaign::STATUS_COMPLETED : SmsCampaign::STATUS_RUNNING,
            'completed_at' => $allDone ? now() : null,
        ]);
    }

    public function cancel(SmsCampaign $campaign): void
    {
        $campaign->update(['status' => SmsCampaign::STATUS_CANCELLED]);
        $campaign->pendingRecipients()->update(['status' => 'failed', 'error_message' => 'لغو شده']);
    }

    public function duplicate(SmsCampaign $campaign, int $userId): SmsCampaign
    {
        return SmsCampaign::create([
            'site_id'         => $campaign->site_id,
            'name'            => 'کپی — ' . $campaign->name,
            'message'         => $campaign->message,
            'audience_type'   => $campaign->audience_type,
            'audience_filter' => $campaign->audience_filter,
            'status'          => SmsCampaign::STATUS_DRAFT,
            'created_by_id'   => $userId,
        ]);
    }

    public function getStats(SmsCampaign $campaign): array
    {
        return [
            'total'       => $campaign->total_recipients,
            'sent'        => $campaign->sent_count,
            'failed'      => $campaign->failed_count,
            'pending'     => $campaign->recipients()->where('status', 'pending')->count(),
            'delivery'    => $campaign->deliveryRate(),
            'sms_parts'   => $campaign->smsCount(),
        ];
    }

    // ── Audience builders ─────────────────────────────────────────────────────

    private function allCustomerMobiles(int $siteId): Collection
    {
        // Unique mobiles from orders placed on this site
        return Order::where('site_id', $siteId)
            ->whereNotNull('customer_mobile')
            ->distinct()
            ->get(['customer_mobile as mobile', 'customer_name as name']);
    }

    private function crmMobiles(SmsCampaign $campaign): Collection
    {
        $q = CrmContact::query()->whereNotNull('mobile');

        // Optional CRM status filter: {crm_status: "customer"}
        $filter = $campaign->audience_filter ?? [];
        if (!empty($filter['crm_status'])) {
            $q->where('status', $filter['crm_status']);
        }

        return $q->get(['mobile', 'name']);
    }

    private function userMobiles(int $siteId): Collection
    {
        return User::where('site_id', $siteId)
            ->whereNotNull('mobile')
            ->where('is_active', true)
            ->get(['mobile', 'name']);
    }

    private function parsedManualMobiles(?array $filter): Collection
    {
        if (empty($filter['mobiles'])) return collect();
        $lines = preg_split('/[\r\n,;]+/', $filter['mobiles']);
        return collect($lines)
            ->map(fn($l) => ['mobile' => trim($l), 'name' => null])
            ->filter(fn($m) => preg_match('/^0?9\d{9}$/', $m['mobile']));
    }
}

<?php

namespace App\Jobs;

use App\Models\WebhookEndpoint;
use App\Models\WebhookEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DispatchWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60; // seconds between retries

    public function __construct(private readonly int $webhookEventId) {}

    public function handle(): void
    {
        $event = WebhookEvent::with('endpoint')->find($this->webhookEventId);
        if (!$event || !$event->endpoint) {
            return;
        }

        $endpoint = $event->endpoint;
        $body     = json_encode([
            'event'   => $event->event_type,
            'payload' => $event->payload,
            'sent_at' => now()->toIso8601String(),
        ]);
        $signature = 'sha256=' . hash_hmac('sha256', $body, $endpoint->secret);

        $event->increment('attempts');
        $event->update(['last_attempted_at' => now()]);

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type'       => 'application/json',
                    'X-Webhook-Event'    => $event->event_type,
                    'X-Webhook-Signature' => $signature,
                ])
                ->post($endpoint->url, json_decode($body, true));

            $statusCode = $response->status();
            $success    = $response->successful();

            $event->update([
                'status'            => $success ? WebhookEvent::STATUS_SENT : WebhookEvent::STATUS_FAILED,
                'response_code'     => $statusCode,
                'response_body'     => mb_substr($response->body(), 0, 2000),
            ]);

            $endpoint->update([
                'last_called_at'    => now(),
                'last_status_code'  => $statusCode,
            ]);

            if (!$success) {
                $this->fail("Webhook returned HTTP {$statusCode}");
            }
        } catch (\Throwable $e) {
            $event->update([
                'status'        => WebhookEvent::STATUS_FAILED,
                'response_body' => $e->getMessage(),
            ]);
            Log::warning("Webhook dispatch failed for event #{$event->id}: {$e->getMessage()}");
            throw $e; // let the queue retry
        }
    }
}

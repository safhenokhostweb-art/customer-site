<?php

namespace App\Services;

use App\Jobs\DispatchWebhookJob;
use App\Models\WebhookEndpoint;
use App\Models\WebhookEvent;

class WebhookDispatchService
{
    /**
     * Dispatch a webhook event to all active endpoints subscribed to $eventType.
     */
    public function dispatch(string $eventType, array $payload): void
    {
        $endpoints = WebhookEndpoint::where('is_active', true)->get();

        foreach ($endpoints as $endpoint) {
            if (!$endpoint->subscribesTo($eventType)) {
                continue;
            }

            $event = WebhookEvent::create([
                'webhook_endpoint_id' => $endpoint->id,
                'event_type'          => $eventType,
                'payload'             => $payload,
                'status'              => WebhookEvent::STATUS_PENDING,
            ]);

            DispatchWebhookJob::dispatch($event->id);
        }
    }
}

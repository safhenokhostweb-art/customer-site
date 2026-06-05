<?php

namespace App\Services;

use App\Models\PushSubscription;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    private WebPush $webPush;

    public function __construct()
    {
        $this->webPush = new WebPush([
            'VAPID' => [
                'subject'    => config('app.url'),
                'publicKey'  => config('push.vapid_public_key'),
                'privateKey' => config('push.vapid_private_key'),
            ],
        ]);
        $this->webPush->setReuseVAPIDHeaders(true);
    }

    public function sendToUser(int $userId, string $title, string $body, string $url = '/'): void
    {
        $subscriptions = PushSubscription::where('user_id', $userId)->get();

        foreach ($subscriptions as $sub) {
            try {
                $subscription = Subscription::create([
                    'endpoint'        => $sub->endpoint,
                    'contentEncoding' => $sub->content_encoding,
                    'keys'            => [
                        'p256dh' => $sub->public_key,
                        'auth'   => $sub->auth_token,
                    ],
                ]);

                $this->webPush->queueNotification(
                    $subscription,
                    json_encode([
                        'title' => $title,
                        'body'  => $body,
                        'url'   => $url,
                        'icon'  => '/favicon.ico',
                        'dir'   => 'rtl',
                        'lang'  => 'fa',
                    ])
                );
            } catch (\Throwable $e) {
                Log::warning('PushNotification queue error: ' . $e->getMessage());
            }
        }

        foreach ($this->webPush->flush() as $report) {
            if (!$report->isSuccess()) {
                // اشتراک نامعتبر — حذف می‌کنیم
                if ($report->isSubscriptionExpired()) {
                    PushSubscription::where('endpoint', $report->getEndpoint())->delete();
                }
            }
        }
    }

    public function sendToAll(string $title, string $body, string $url = '/'): void
    {
        $subscriptions = PushSubscription::all();

        foreach ($subscriptions as $sub) {
            try {
                $subscription = Subscription::create([
                    'endpoint'        => $sub->endpoint,
                    'contentEncoding' => $sub->content_encoding,
                    'keys'            => [
                        'p256dh' => $sub->public_key,
                        'auth'   => $sub->auth_token,
                    ],
                ]);

                $this->webPush->queueNotification(
                    $subscription,
                    json_encode([
                        'title' => $title,
                        'body'  => $body,
                        'url'   => $url,
                        'icon'  => '/favicon.ico',
                        'dir'   => 'rtl',
                        'lang'  => 'fa',
                    ])
                );
            } catch (\Throwable $e) {}
        }

        foreach ($this->webPush->flush() as $report) {
            if ($report->isSubscriptionExpired()) {
                PushSubscription::where('endpoint', $report->getEndpoint())->delete();
            }
        }
    }
}

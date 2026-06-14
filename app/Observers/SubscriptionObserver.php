<?php

namespace App\Observers;

use App\Models\Subscription;
use App\Services\AdminNotificationService;

class SubscriptionObserver
{
    public function created(Subscription $subscription): void
    {
        $plan   = optional($subscription->plan);
        $user   = optional($subscription->user);
        $name   = $user->name ?? $user->mobile ?? 'کاربر';
        $status = match ($subscription->status) {
            'trial'  => 'آزمایشی',
            'active' => 'فعال',
            default  => $subscription->status,
        };

        AdminNotificationService::send(
            type:  'subscription',
            title: "اشتراک {$status}: {$plan->name}",
            body:  $name,
        );
    }
}

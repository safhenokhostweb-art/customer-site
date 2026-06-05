<?php

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class ExpireSubscriptions extends Command
{
    protected $signature   = 'subscriptions:expire {--dry-run : فقط تعداد نمایش داده شود بدون اعمال تغییر}';
    protected $description = 'اشتراک‌هایی که تاریخ پایانشان گذشته را منقضی می‌کند';

    public function handle(SubscriptionService $service): int
    {
        if ($this->option('dry-run')) {
            $count = \App\Models\Subscription::whereIn('status', [
                    \App\Models\Subscription::STATUS_ACTIVE,
                    \App\Models\Subscription::STATUS_TRIAL,
                ])
                ->where('ends_at', '<', now())
                ->count();

            $this->info("تعداد اشتراک‌های قابل انقضا: {$count} (حالت dry-run — تغییری اعمال نشد)");
            return self::SUCCESS;
        }

        $count = $service->expireOverdue();

        if ($count > 0) {
            $this->info("✓ {$count} اشتراک منقضی شد.");
        } else {
            $this->line('اشتراک منقضی‌شدنی یافت نشد.');
        }

        return self::SUCCESS;
    }
}

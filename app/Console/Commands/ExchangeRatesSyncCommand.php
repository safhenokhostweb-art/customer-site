<?php

namespace App\Console\Commands;

use App\Services\ExchangeRates\ExchangeRateException;
use App\Services\ExchangeRates\ExchangeRateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Scheduled hourly (routes/console.php). A no-op unless the site admin
 * enabled the online provider, so it is safe on every installation.
 */
class ExchangeRatesSyncCommand extends Command
{
    protected $signature = 'exchange-rates:sync';

    protected $description = 'به‌روزرسانی نرخ ارز از سرویس آنلاین تنظیم‌شده (در صورت فعال بودن)';

    public function handle(ExchangeRateService $rates): int
    {
        if (!$rates->providerSettings()['enabled']) {
            $this->line('Online exchange-rate sync is disabled.');
            return self::SUCCESS;
        }

        try {
            $updated = $rates->syncOnline();
        } catch (ExchangeRateException $e) {
            Log::warning('exchange_rates.sync_failed', ['message' => $e->getMessage()]);
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info('Updated: ' . implode(', ', array_keys($updated)));

        return self::SUCCESS;
    }
}

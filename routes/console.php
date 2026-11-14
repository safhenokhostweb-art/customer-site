<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Online exchange rates (App\Services\ExchangeRates): a no-op unless the
// site admin enabled the online provider.
Schedule::command('exchange-rates:sync')->hourly()->withoutOverlapping(10);

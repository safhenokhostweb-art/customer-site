<?php

namespace App\Modules\SolarCalculator;

use App\Modules\SolarCalculator\Services\SolarAccessPolicy;
use App\Modules\SolarCalculator\Services\SolarCalculationService;
use App\Modules\SolarCalculator\Services\SolarCalculatorSettings;
use App\Modules\SolarCalculator\Services\SolarLeadService;
use App\Services\ExchangeRates\ExchangeRateService;
use Illuminate\Support\ServiceProvider;

/**
 * Solar calculator module (license key `solar_calculator`). Self-contained
 * under app/Modules/SolarCalculator; the host touches it only through the
 * canvas seams (ThemeDesignController palette, ThemeCanvasRenderer
 * dispatch) and the admin menu.
 */
class SolarCalculatorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One instance per request/job: settings and the compiled formula
        // set are memoized on the instance, never in a static.
        $this->app->scoped(SolarCalculatorSettings::class);
        $this->app->scoped(ExchangeRateService::class);
        $this->app->scoped(SolarCalculationService::class);
        $this->app->scoped(SolarAccessPolicy::class);
        $this->app->scoped(SolarLeadService::class);
    }

    public function boot(): void
    {
        // Controllers bind {lead}/{formula} implicitly by type-hint; no
        // global Route::model (it would rebind other modules' {lead}).
        if (!$this->app->routesAreCached()) {
            $this->loadRoutesFrom(__DIR__ . '/routes.php');
        }
    }
}

<?php

namespace Tests\Feature\SolarCalculator\Concerns;

use App\Models\CurrencyExchangeRate;
use App\Models\License;
use App\Models\User;
use App\Modules\SolarCalculator\Services\SolarCalculatorSettings;

trait SolarCalculatorFixtures
{
    protected function licenseModules(array $modules = ['solar_calculator']): License
    {
        return License::query()->create([
            'license_key' => 'TEST-' . uniqid(),
            'domain' => null,
            'expires_at' => now()->addYear(),
            'modules' => $modules,
            'platform_valid' => true,
            'last_validated_at' => now(),
        ]);
    }

    protected function setUsdRate(float $toman): void
    {
        CurrencyExchangeRate::query()->where('code', 'USD')->update(['rate_toman' => $toman, 'is_active' => true]);
    }

    protected function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    protected function setAccessMode(string $mode): void
    {
        app(SolarCalculatorSettings::class)->save('access', ['mode' => $mode, 'require_email' => false]);
        app(SolarCalculatorSettings::class)->flush();
    }

    /** A valid on-grid request: 450 kWh/month in Tehran (PSH 5.0). */
    protected function onGridPayload(array $overrides = []): array
    {
        return array_merge([
            'system_type' => 'on_grid',
            'consumption_mode' => 'kwh',
            'monthly_kwh' => '450',
            'region' => 'tehran',
        ], $overrides);
    }

    protected function contact(array $overrides = []): array
    {
        return array_merge(['name' => 'مریم احمدی', 'mobile' => '۰۹۱۲۱۲۳۴۵۶۷', 'email' => 'maryam@example.com'], $overrides);
    }
}

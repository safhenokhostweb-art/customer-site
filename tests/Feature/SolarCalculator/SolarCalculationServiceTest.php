<?php

namespace Tests\Feature\SolarCalculator;

use App\Modules\SolarCalculator\Models\SolarCalculatorFormula;
use App\Modules\SolarCalculator\Services\SolarCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Feature\SolarCalculator\Concerns\SolarCalculatorFixtures;
use Tests\TestCase;

/**
 * The factory formula set, evaluated end to end against hand-computed
 * values, plus the exchange-rate dependency of the price.
 */
class SolarCalculationServiceTest extends TestCase
{
    use RefreshDatabase;
    use SolarCalculatorFixtures;

    private function service(): SolarCalculationService
    {
        return app(SolarCalculationService::class);
    }

    private function calc(array $raw): array
    {
        $svc = $this->service();

        return $svc->calculate($svc->normalizeInput($raw));
    }

    public function test_default_formulas_are_installed_and_lint_clean(): void
    {
        $this->assertGreaterThanOrEqual(30, SolarCalculatorFormula::query()->count());
        $this->assertSame([], $this->service()->engine()->lint($this->service()->inputNames()));
    }

    public function test_on_grid_sizing_and_itemized_price_match_hand_calculation(): void
    {
        $this->setUsdRate(100000);
        $r = $this->calc($this->onGridPayload());
        $v = $r['values'];

        // 450 kWh/month → 15 kWh/day; 15 / (5.0 × 0.78) = 3.846 kW → 7 × 550 W.
        $this->assertSame(7.0, $v['panel_count']);
        $this->assertEqualsWithDelta(3.85, $v['system_kw'], 1e-9);
        $this->assertSame(4.5, $v['inverter_kw']);             // roundup(4.4275, 0.5)
        $this->assertSame(0.0, $v['battery_count']);
        $this->assertEqualsWithDelta(5480.475, $v['annual_kwh'], 1e-6);
        $this->assertEqualsWithDelta(23.478, $v['area_m2'], 1e-6);

        $this->assertEqualsWithDelta(77_000_000, $v['price_panels'], 1e-3);
        $this->assertEqualsWithDelta(49_500_000, $v['price_inverter'], 1e-3);
        $this->assertEqualsWithDelta(25_300_000, $v['price_bos'], 1e-3);
        $this->assertEqualsWithDelta(13_475_000, $v['price_installation'], 1e-3);
        $this->assertEqualsWithDelta(181_802_500, $v['total_price'], 1e-3);

        $this->assertTrue($r['price']['available']);
        $this->assertSame(181802500.0, $r['price']['total']);
        $this->assertStringContainsString('۱۸۱٬۸۰۲٬۵۰۰', $r['price']['total_html']);

        $labels = array_column($r['quote']['lines'], 'label');
        $this->assertContains('پنل خورشیدی مونوکریستال', $labels);
        $this->assertNotContains('بانک باتری لیتیومی', $labels, 'zero battery line is hidden on-grid');
        $this->assertSame('توان هر پنل ۵۵۰ وات', $r['quote']['lines'][0]['detail']);
        $this->assertSame('۷', $r['quote']['lines'][0]['qty_display']);

        $cardKeys = array_column($r['cards'], 'key');
        $this->assertNotContains('battery_bank_kwh', $cardKeys);
        $this->assertSame('۳٫۹', $r['cards'][0]['display']); // 3.85 → 1 decimal
    }

    public function test_price_is_unavailable_not_zero_without_a_usd_rate(): void
    {
        $r = $this->calc($this->onGridPayload());

        $this->assertFalse($r['price']['available']);
        $this->assertSame(['USD'], $r['price']['missing_rates']);
        $this->assertNull($r['price']['total']);
        $this->assertNull($r['quote']);
        $this->assertSame(7.0, $r['values']['panel_count'], 'sizing still works without a rate');
    }

    public function test_off_grid_sizes_the_battery_bank_for_autonomy_days(): void
    {
        $this->setUsdRate(100000);
        $v = $this->calc(['system_type' => 'off_grid', 'consumption_mode' => 'kwh', 'monthly_kwh' => '300', 'region' => 'yazd'])['values'];

        // 10 kWh/day × 1.5 days / 0.9 DoD = 16.67 kWh → 4 × 5.12 kWh.
        $this->assertSame(5.0, $v['panel_count']);
        $this->assertEqualsWithDelta(16.6667, $v['battery_kwh'], 1e-3);
        $this->assertSame(4.0, $v['battery_count']);
        $this->assertEqualsWithDelta(20.48, $v['battery_bank_kwh'], 1e-9);
        $this->assertEqualsWithDelta(4 * 1150 * 100000, $v['price_batteries'], 1e-3);
    }

    public function test_hybrid_uses_backup_hours_and_bill_mode_uses_the_tariff(): void
    {
        $v = $this->calc(['system_type' => 'hybrid', 'consumption_mode' => 'bill', 'monthly_bill' => '۹۰۰٬۰۰۰', 'region' => 'tehran', 'backup_hours' => '۶'])['values'];

        // 900,000 toman / 2,000 toman per kWh = 450 kWh → 15 kWh/day; 15/24 × 6 / 0.9.
        $this->assertSame(450.0, $v['monthly_kwh']);
        $this->assertEqualsWithDelta(4.1667, $v['battery_kwh'], 1e-3);
        $this->assertSame(1.0, $v['battery_count']);
    }

    public function test_roof_check_flags_insufficient_space(): void
    {
        $this->assertFalse($this->calc($this->onGridPayload(['roof_area' => '10']))['roof_fits']);
        $this->assertTrue($this->calc($this->onGridPayload(['roof_area' => '100']))['roof_fits']);
        $this->assertTrue($this->calc($this->onGridPayload())['roof_fits'], 'unknown area is not a failure');
    }

    public function test_an_edited_formula_changes_the_output_immediately(): void
    {
        SolarCalculatorFormula::query()->where('key', 'panel_watt')->update(['expression' => '400']);
        $v = $this->calc($this->onGridPayload())['values'];

        $this->assertSame(10.0, $v['panel_count']); // ceil(3846 / 400)
    }

    public function test_input_validation_rejects_bad_values_in_persian(): void
    {
        try {
            $this->service()->normalizeInput(['system_type' => 'nuclear', 'consumption_mode' => 'kwh', 'monthly_kwh' => '-5', 'region' => 'atlantis']);
            $this->fail('invalid input accepted');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $this->assertArrayHasKey('system_type', $errors);
            $this->assertArrayHasKey('monthly_kwh', $errors);
            $this->assertArrayHasKey('region', $errors);
        }
    }
}

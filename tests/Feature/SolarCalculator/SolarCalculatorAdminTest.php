<?php

namespace Tests\Feature\SolarCalculator;

use App\Models\CurrencyExchangeRate;
use App\Models\User;
use App\Modules\SolarCalculator\Models\SolarCalculatorFormula;
use App\Modules\SolarCalculator\Models\SolarCalculatorLead;
use App\Modules\SolarCalculator\Services\SolarCalculatorSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Feature\SolarCalculator\Concerns\SolarCalculatorFixtures;
use Tests\TestCase;

class SolarCalculatorAdminTest extends TestCase
{
    use RefreshDatabase;
    use SolarCalculatorFixtures;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->licenseModules();
        $this->admin = $this->admin();
    }

    public function test_every_admin_page_renders(): void
    {
        $this->setUsdRate(95000);
        $this->postJson(route('solar-calculator.quote.store'), $this->onGridPayload($this->contact()))->assertCreated();
        $lead = SolarCalculatorLead::query()->sole();
        $formula = SolarCalculatorFormula::query()->where('key', 'panel_watt')->sole();

        $this->actingAs($this->admin);
        $this->get(route('admin.solar-calculator.leads.index'))->assertOk()->assertSee($lead->quote_number);
        $this->get(route('admin.solar-calculator.leads.show', $lead))->assertOk()->assertSee('مریم احمدی');
        $this->get(route('admin.solar-calculator.leads.quote', $lead))->assertOk()->assertSee('پیش‌فاکتور سیستم خورشیدی');
        $this->get(route('admin.solar-calculator.formulas.index'))->assertOk()->assertSee('panel_watt');
        $this->get(route('admin.solar-calculator.formulas.edit', $formula))->assertOk();
        foreach (['access', 'company', 'ui', 'regions', 'results'] as $tab) {
            $this->get(route('admin.solar-calculator.settings.edit', ['tab' => $tab]))->assertOk();
        }
        $this->get(route('admin.exchange-rates.index'))->assertOk()->assertSee('USD');

        $csv = $this->get(route('admin.solar-calculator.leads.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString($lead->quote_number, $csv);
    }

    public function test_a_non_admin_cannot_open_the_module_admin(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.solar-calculator.leads.index'))->assertStatus(403);
    }

    public function test_formula_edits_are_validated_as_a_whole_set(): void
    {
        $this->actingAs($this->admin);
        $panel = SolarCalculatorFormula::query()->where('key', 'panel_count')->sole();
        $base = ['label' => $panel->label, 'group' => $panel->group, 'unit' => $panel->unit];

        $this->put(route('admin.solar-calculator.formulas.update', $panel), $base + ['expression' => 'ceil(required_kw * 1000 /'])
            ->assertSessionHasErrors('expression');
        $this->put(route('admin.solar-calculator.formulas.update', $panel), $base + ['expression' => 'ceil(not_a_variable)'])
            ->assertSessionHasErrors('expression');
        $this->put(route('admin.solar-calculator.formulas.update', $panel), $base + ['expression' => 'system_kw * 2'])
            ->assertSessionHasErrors('expression'); // system_kw depends on panel_count → cycle
        $this->put(route('admin.solar-calculator.formulas.update', $panel), $base + ['expression' => '1 / (psh - psh)'])
            ->assertSessionHasErrors('expression'); // dry run divides by zero
        $this->assertSame('ceil(required_kw * 1000 / panel_watt)', $panel->fresh()->expression);

        $this->put(route('admin.solar-calculator.formulas.update', $panel), $base + ['expression' => 'ceil(required_kw * 1000 / panel_watt) + 1'])
            ->assertRedirect(route('admin.solar-calculator.formulas.index'));
        $this->assertSame('ceil(required_kw * 1000 / panel_watt) + 1', $panel->fresh()->expression);

        $this->post(route('admin.solar-calculator.formulas.restore', $panel))->assertRedirect();
        $this->assertSame('ceil(required_kw * 1000 / panel_watt)', $panel->fresh()->expression);
    }

    public function test_custom_formula_lifecycle_and_delete_guards(): void
    {
        $this->actingAs($this->admin);

        $this->post(route('admin.solar-calculator.formulas.store'), ['key' => 'psh', 'label' => 'x', 'group' => 'custom', 'expression' => '1'])
            ->assertSessionHasErrors('key'); // shadows an input
        $this->post(route('admin.solar-calculator.formulas.store'), ['key' => 'Bad Key', 'label' => 'x', 'group' => 'custom', 'expression' => '1'])
            ->assertSessionHasErrors('key');

        $this->post(route('admin.solar-calculator.formulas.store'), ['key' => 'monitoring_fee', 'label' => 'پایش آنلاین', 'group' => 'custom', 'expression' => 'system_kw * 50000', 'unit' => 'تومان'])
            ->assertRedirect(route('admin.solar-calculator.formulas.index'));
        $custom = SolarCalculatorFormula::query()->where('key', 'monitoring_fee')->sole();

        $system = SolarCalculatorFormula::query()->where('key', 'total_price')->sole();
        $this->delete(route('admin.solar-calculator.formulas.destroy', $system))->assertSessionHas('error');
        $used = SolarCalculatorFormula::query()->where('key', 'panel_watt')->sole();
        $this->delete(route('admin.solar-calculator.formulas.destroy', $used))->assertSessionHas('error'); // referenced
        $this->assertNotNull($used->fresh());

        $this->delete(route('admin.solar-calculator.formulas.destroy', $custom))->assertSessionHas('success');
        $this->assertNull($custom->fresh());
    }

    public function test_settings_sections_save_and_feed_the_calculator(): void
    {
        $this->actingAs($this->admin);

        $this->put(route('admin.solar-calculator.settings.update'), [
            'section' => 'access',
            'access' => ['mode' => 'login_quote'],
            'calculator' => ['default_system_type' => 'hybrid', 'default_backup_hours' => 5, 'max_monthly_kwh' => 5000, 'show_price' => '1'],
        ])->assertRedirect();
        $this->put(route('admin.solar-calculator.settings.update'), [
            'section' => 'regions',
            'regions' => [['key' => 'kish', 'label' => 'کیش', 'psh' => 6.1]],
        ])->assertRedirect();
        $this->put(route('admin.solar-calculator.settings.update'), [
            'section' => 'ui',
            'ui' => ['title' => 'برآورد نیروگاه شما', 'banner_image' => 'javascript:alert(1)'],
        ])->assertSessionHasErrors('ui_upload.banner_image');
        $this->put(route('admin.solar-calculator.settings.update'), [
            'section' => 'company',
            'company' => ['name' => 'انرژی پاک', 'quote_prefix' => 'PV', 'validity_days' => 10, 'accent_color' => '#0f766e'],
        ])->assertRedirect();

        $settings = app(SolarCalculatorSettings::class);
        $settings->flush();
        $this->assertSame('login_quote', $settings->accessMode());
        $this->assertSame('hybrid', $settings->section('calculator')['default_system_type']);
        $this->assertSame([['key' => 'kish', 'label' => 'کیش', 'psh' => 6.1]], $settings->regions());
        $this->assertSame('PV', $settings->company()['quote_prefix']);

        $this->put(route('admin.solar-calculator.settings.update'), ['section' => 'results', 'result_cards' => [['key' => 'no_such_formula', 'label' => 'x', 'decimals' => 0]]])
            ->assertSessionHasErrors('result_cards.0.key');

        $this->post(route('admin.solar-calculator.settings.reset'), ['section' => 'regions'])->assertRedirect();
        $settings->flush();
        $this->assertGreaterThan(20, count($settings->regions()));
    }

    public function test_lead_status_update_and_soft_delete(): void
    {
        $this->postJson(route('solar-calculator.quote.store'), $this->onGridPayload($this->contact()))->assertCreated();
        $lead = SolarCalculatorLead::query()->sole();
        $this->actingAs($this->admin);

        $this->put(route('admin.solar-calculator.leads.update', $lead), ['status' => 'site_visit', 'admin_note' => 'بازدید شنبه'])->assertRedirect();
        $this->assertSame('site_visit', $lead->fresh()->status);
        $this->put(route('admin.solar-calculator.leads.update', $lead), ['status' => 'hacked'])->assertSessionHasErrors('status');

        $this->delete(route('admin.solar-calculator.leads.destroy', $lead))->assertRedirect(route('admin.solar-calculator.leads.index'));
        $this->assertSoftDeleted($lead);
    }

    public function test_manual_rates_and_online_sync(): void
    {
        $this->actingAs($this->admin);

        $this->put(route('admin.exchange-rates.rates.update'), ['rates' => ['USD' => '۹۵٬۵۰۰', 'EUR' => ''], 'active' => ['USD' => '1', 'EUR' => '1']])->assertRedirect();
        $usd = CurrencyExchangeRate::query()->where('code', 'USD')->sole();
        $this->assertSame(95500.0, $usd->rate_toman);
        $this->assertSame('manual', $usd->source);
        $this->assertFalse(CurrencyExchangeRate::query()->where('code', 'AED')->sole()->is_active);

        $this->put(route('admin.exchange-rates.provider.update'), ['enabled' => '1', 'url' => 'http://insecure.test', 'unit' => 'toman'])
            ->assertSessionHasErrors('url');
        $this->put(route('admin.exchange-rates.provider.update'), ['enabled' => '1', 'url' => 'https://93.184.216.34/rates', 'unit' => 'rial', 'paths' => ['USD' => 'data.usd.sell']])
            ->assertRedirect();

        Http::fake(['93.184.216.34/*' => Http::response(['data' => ['usd' => ['sell' => '1,020,000']]])]);
        $this->post(route('admin.exchange-rates.sync'))->assertSessionHas('success');

        $usd->refresh();
        $this->assertSame(102000.0, $usd->rate_toman); // rial → toman
        $this->assertSame('online', $usd->source);
    }
}

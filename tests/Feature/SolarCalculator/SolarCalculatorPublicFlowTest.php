<?php

namespace Tests\Feature\SolarCalculator;

use App\Models\CrmActivity;
use App\Models\CrmContact;
use App\Models\User;
use App\Modules\SolarCalculator\Models\SolarCalculatorLead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\SolarCalculator\Concerns\SolarCalculatorFixtures;
use Tests\TestCase;

class SolarCalculatorPublicFlowTest extends TestCase
{
    use RefreshDatabase;
    use SolarCalculatorFixtures;

    public function test_every_route_is_closed_on_a_site_without_the_license(): void
    {
        $this->licenseModules(['shop']);

        $this->postJson(route('solar-calculator.calculate'), $this->onGridPayload())->assertStatus(403);
        $this->postJson(route('solar-calculator.quote.store'), $this->onGridPayload($this->contact()))->assertStatus(403);
        $this->get(route('solar-calculator.quote.show', ['token' => str_repeat('a', 48)]))->assertStatus(403);
        $this->actingAs($this->admin())->get(route('admin.solar-calculator.leads.index'))->assertStatus(403);
        $this->assertSame(0, SolarCalculatorLead::query()->count());
    }

    public function test_calculate_returns_only_display_data(): void
    {
        $this->licenseModules();
        $this->setUsdRate(100000);

        $res = $this->postJson(route('solar-calculator.calculate'), $this->onGridPayload())->assertOk();

        $res->assertJsonPath('ok', true)
            ->assertJsonPath('result.price.available', true)
            ->assertJsonPath('result.cards.0.key', 'system_kw');
        $this->assertArrayNotHasKey('values', $res->json('result'), 'raw formula values stay server-side');
        $this->assertStringContainsString('<svg', $res->json('result.cards.0.icon_svg'));
        $this->assertSame(0, SolarCalculatorLead::query()->count(), 'calculating creates no lead');
    }

    public function test_calculate_validation_errors_are_422_with_field_keys(): void
    {
        $this->licenseModules();

        $this->postJson(route('solar-calculator.calculate'), ['system_type' => 'on_grid', 'consumption_mode' => 'kwh'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['monthly_kwh', 'region']);
    }

    public function test_quote_submission_captures_lead_and_crm_before_releasing_the_quote(): void
    {
        $this->licenseModules();
        $this->setUsdRate(100000);

        $res = $this->postJson(route('solar-calculator.quote.store'), $this->onGridPayload($this->contact(['note' => 'سقف شیروانی'])))
            ->assertCreated()
            ->assertJsonPath('ok', true);

        $lead = SolarCalculatorLead::query()->sole();
        $this->assertSame('09121234567', $lead->mobile, 'Persian digits normalized');
        $this->assertSame('SQ-' . str_pad((string) $lead->id, 5, '0', STR_PAD_LEFT), $lead->quote_number);
        $this->assertSame(181802500.0, $lead->total_price);
        $this->assertEquals(100000, $lead->exchange_rates['USD']['rate']);
        $this->assertTrue($lead->quote['available']);
        $this->assertSame(48, strlen($lead->public_token));

        $crm = CrmContact::query()->sole();
        $this->assertSame($crm->id, $lead->crm_contact_id);
        $this->assertSame('form', $crm->source);
        $this->assertSame('lead', $crm->status);
        $this->assertContains('solar_calculator', $crm->tags);
        $this->assertSame('تهران', $crm->city);
        $this->assertStringContainsString($lead->quote_number, CrmActivity::query()->sole()->subject);

        $res->assertJsonPath('quote_number', $lead->quote_number);
        $this->assertStringEndsWith('?print=1', $res->json('print_url'));

        $this->get($res->json('quote_url'))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee($lead->quote_number)
            ->assertSee('مریم احمدی')
            ->assertSee('پنل خورشیدی مونوکریستال')
            ->assertSee('۱۸۱٬۸۰۲٬۵۰۰', false);
        $this->assertNotNull($lead->fresh()->quote_viewed_at);
    }

    public function test_a_returning_mobile_reuses_the_crm_contact(): void
    {
        $this->licenseModules();
        $existing = CrmContact::query()->create(['name' => 'قبلی', 'mobile' => '09121234567', 'source' => 'manual', 'status' => 'customer', 'tags' => ['vip']]);

        $this->postJson(route('solar-calculator.quote.store'), $this->onGridPayload($this->contact()))->assertCreated();

        $this->assertSame(1, CrmContact::query()->count());
        $existing->refresh();
        $this->assertSame('customer', $existing->status, 'an existing customer is not downgraded to lead');
        $this->assertEqualsCanonicalizing(['vip', 'solar_calculator'], $existing->tags);
        $this->assertSame($existing->id, SolarCalculatorLead::query()->sole()->crm_contact_id);
    }

    public function test_quote_without_rate_is_issued_without_prices(): void
    {
        $this->licenseModules();

        $url = $this->postJson(route('solar-calculator.quote.store'), $this->onGridPayload($this->contact()))->assertCreated()->json('quote_url');

        $lead = SolarCalculatorLead::query()->sole();
        $this->assertNull($lead->total_price);
        $this->get($url)->assertOk()->assertSee('پس از تأیید نرخ ارز');
    }

    public function test_contact_validation_and_honeypot(): void
    {
        $this->licenseModules();

        $this->postJson(route('solar-calculator.quote.store'), $this->onGridPayload(['name' => '', 'mobile' => '123']))
            ->assertStatus(422)->assertJsonValidationErrors(['name', 'mobile']);

        $this->postJson(route('solar-calculator.quote.store'), $this->onGridPayload($this->contact(['website' => 'http://spam'])))
            ->assertStatus(422);

        $this->assertSame(0, SolarCalculatorLead::query()->count());
        $this->assertSame(0, CrmContact::query()->count());
    }

    public function test_unknown_or_malformed_token_is_not_found(): void
    {
        $this->licenseModules();

        $this->get(route('solar-calculator.quote.show', ['token' => str_repeat('Z', 48)]))->assertNotFound();
        $this->get('/solar-calculator/quote/short')->assertNotFound();
    }

    public function test_login_results_mode_requires_login_to_calculate(): void
    {
        $this->licenseModules();
        $this->setAccessMode('login_results');

        $this->postJson(route('solar-calculator.calculate'), $this->onGridPayload())
            ->assertStatus(401)->assertJsonPath('login_required', true);
        $this->postJson(route('solar-calculator.quote.store'), $this->onGridPayload($this->contact()))->assertStatus(401);

        $this->actingAs(User::factory()->create())
            ->postJson(route('solar-calculator.calculate'), $this->onGridPayload())->assertOk();
    }

    public function test_login_quote_mode_binds_the_quote_to_the_first_account_that_opens_it(): void
    {
        $this->licenseModules();
        $this->setAccessMode('login_quote');

        $this->postJson(route('solar-calculator.calculate'), $this->onGridPayload())->assertOk();
        $url = $this->postJson(route('solar-calculator.quote.store'), $this->onGridPayload($this->contact()))->assertCreated()->json('quote_url');

        $this->get($url)->assertRedirect(route('login'));

        $owner = User::factory()->create();
        $this->actingAs($owner)->get($url)->assertOk();
        $this->assertSame($owner->id, SolarCalculatorLead::query()->sole()->user_id);

        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
    }

    public function test_logged_in_visitor_is_linked_to_the_lead(): void
    {
        $this->licenseModules();
        $user = User::factory()->create(['name' => 'علی رضایی', 'mobile' => '09351112233']);

        $this->actingAs($user)->postJson(route('solar-calculator.quote.store'), $this->onGridPayload())->assertCreated();

        $lead = SolarCalculatorLead::query()->sole();
        $this->assertSame($user->id, $lead->user_id);
        $this->assertSame('علی رضایی', $lead->name, 'contact prefilled from the account');
        $this->assertSame('09351112233', $lead->mobile);
    }
}

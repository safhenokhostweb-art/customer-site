<?php

namespace Tests\Feature\ProductionStability;

use App\Helpers\SiteHelper;
use App\Models\License;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Domain-aware License::current() resolution (multi-tenant safe).
 *
 * Background: an installation can hold several License rows (a valid
 * production-domain license and a stale/invalid 127.0.0.1 row from an
 * earlier local activation). The previous `latest()` selection handed the
 * invalid localhost row to requests served under customer-site.test and
 * wrongly denied the Canvas editor via EnsureLicenseValid.
 *
 * The resolution contract proven here:
 *  1. a host-matched row always wins for its own host,
 *  2. an invalid row for another host is never selected,
 *  3. platform_valid=false rows fail validation even when selected,
 *  4. legacy domain-less rows keep resolving on the app's own host only,
 *  5. unknown hosts resolve nothing,
 *  6. the tenant context and the license middleware behave consistently.
 */
class LicenseResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        // phpunit.xml does not set APP_URL; align the app host with the
        // scenario's production domain so request()->getHost() defaults to
        // customer-site.test exactly like real local usage.
        config(['app.url' => 'http://customer-site.test']);
        // config() alone does not move an already-captured request, and a
        // runner without .env captures "localhost". Bind the scenario's
        // request so host-aware resolution is the same everywhere.
        $this->app->instance('request', Request::create('http://customer-site.test'));
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    private function seedProductionLicense(array $overrides = []): License
    {
        return License::create(array_merge([
            'license_key' => 'PROD-KEY-XXXX-YYYY',
            'domain' => 'customer-site.test',
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => ['shop', 'blog'],
        ], $overrides));
    }

    private function seedLocalhostLicense(array $overrides = []): License
    {
        return License::create(array_merge([
            'license_key' => 'LOCAL-KEY-XXXX-YYYY',
            'domain' => '127.0.0.1',
            'platform_valid' => false,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => ['shop'],
        ], $overrides));
    }

    // ── 1. customer-site.test selects license #1 ────────────────────────────

    public function test_customer_site_test_request_selects_the_matching_license(): void
    {
        $prod = $this->seedProductionLicense();
        // Created LATER → old latest() logic would have picked this one.
        $this->seedLocalhostLicense();

        $current = License::current();

        $this->assertNotNull($current);
        $this->assertSame($prod->id, $current->id);
        $this->assertSame('customer-site.test', $current->domain);
    }

    public function test_host_match_wins_regardless_of_creation_order(): void
    {
        $this->seedLocalhostLicense();
        $prod = $this->seedProductionLicense(); // newest row, matching host

        $this->assertSame($prod->id, License::current()?->id);
    }

    public function test_explicit_host_argument_overrides_request_host(): void
    {
        $prod = $this->seedProductionLicense();
        $this->seedLocalhostLicense();

        $this->assertSame($prod->id, License::current('customer-site.test')?->id);
        $this->assertSame('127.0.0.1', License::current('127.0.0.1')?->domain);
    }

    public function test_domain_normalization_applies_during_resolution(): void
    {
        // Stored with scheme/trailing slash/uppercase — still matches.
        License::create([
            'license_key' => 'NORM-KEY-XXXX-YYYY',
            'domain' => 'HTTPS://Customer-Site.Test/',
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'modules' => ['shop'],
        ]);

        $current = License::current('customer-site.test');

        $this->assertNotNull($current);
        $this->assertSame('NORM-KEY-XXXX-YYYY', $current->license_key);
    }

    // ── 1b. multiple rows for the SAME host — newest-ACTIVE wins, never newest-blind ─

    public function test_stale_revoked_row_for_the_same_host_never_masks_an_active_license(): void
    {
        // The EXACT reported incident shape, later reproduced in the
        // authoritative DB: a newer customer-site.test row whose platform
        // re-validation recorded platform_valid=false must not blind the
        // resolver to the older, still-active production license.
        $prod = $this->seedProductionLicense();
        $stale = $this->seedProductionLicense([
            'license_key' => 'STALE-KEY-XXXX-YYYY',
            'platform_valid' => false,
        ]);

        $this->assertGreaterThan($prod->id, $stale->id);
        $this->assertSame($prod->id, License::current()?->id);
        $this->assertTrue(License::current()?->isValid());
    }

    public function test_expired_row_for_the_same_host_never_masks_an_active_license(): void
    {
        $prod = $this->seedProductionLicense();
        $expiredNewer = $this->seedProductionLicense([
            'license_key' => 'EXPIRED-KEY-XXXX-YYYY',
            'expires_at' => now()->subDay(),
        ]);

        $this->assertSame($prod->id, License::current()?->id);
    }

    public function test_when_all_rows_for_a_host_are_inactive_the_newest_still_resolves(): void
    {
        // Revocation observability contract: EnsureLicenseValid must still
        // be able to LOAD a license for the host so its platform
        // re-validation can run and record the revocation state.
        $older = $this->seedProductionLicense(['platform_valid' => false]);
        $newer = $this->seedProductionLicense([
            'license_key' => 'NEWER-DEAD-KEY',
            'platform_valid' => false,
        ]);

        $selected = License::current();
        $this->assertNotNull($selected);
        $this->assertSame($newer->id, $selected->id);
        $this->assertFalse($selected->isValid());
    }

    // ── 2. invalid 127.0.0.1 license is never selected for customer-site.test ─

    public function test_invalid_localhost_license_is_never_selected_for_the_production_host(): void
    {
        $prod = $this->seedProductionLicense();
        $local = $this->seedLocalhostLicense();

        $this->assertNotSame($local->id, License::current()?->id);
        $this->assertSame($prod->id, License::current()?->id);
        // The localhost row still exists (never deleted) and only resolves on ITS host:
        $this->assertDatabaseHas('licenses', ['id' => $local->id]);
        $this->assertSame($local->id, License::current('127.0.0.1')?->id);
    }

    public function test_each_host_resolves_its_own_row_in_isolation(): void
    {
        $prod = $this->seedProductionLicense(['license_key' => 'PROD-A-XXXX']);
        $local = $this->seedLocalhostLicense(['license_key' => 'LOCAL-A-XXXX', 'platform_valid' => true]);

        $this->assertSame($prod->id, License::current('customer-site.test')?->id);
        $this->assertSame($local->id, License::current('127.0.0.1')?->id);
    }

    // ── 3. platform_valid=false licenses fail validation ─────────────────────

    public function test_platform_invalid_license_fails_validation_even_when_selected(): void
    {
        // Only an invalid license exists for the requested host: current()
        // still SELECTS it (so middleware can re-validate/record revocation),
        // but isValid() must reject it.
        $invalid = $this->seedLocalhostLicense();

        $selected = License::current('127.0.0.1');
        $this->assertNotNull($selected);
        $this->assertSame($invalid->id, $selected->id);
        $this->assertFalse($selected->isValid('127.0.0.1'));
    }

    public function test_revoked_license_fails_validation_on_its_own_host(): void
    {
        $revoked = $this->seedProductionLicense(['platform_valid' => false]);

        $selected = License::current('customer-site.test');
        $this->assertNotNull($selected);
        $this->assertSame($revoked->id, $selected->id);
        $this->assertFalse($selected->isValid('customer-site.test'));

        $context = app(TenantContext::class);
        $this->assertNull($context->current()); // tenant context only accepts valid licenses
    }

    public function test_expired_license_fails_validation(): void
    {
        $expired = $this->seedProductionLicense(['expires_at' => now()->subDay()]);

        $selected = License::current('customer-site.test');
        $this->assertSame($expired->id, $selected?->id);
        $this->assertFalse($selected->isValid('customer-site.test'));
    }

    // ── 4. legacy / edge-case compatibility ──────────────────────────────────

    public function test_legacy_domain_less_license_resolves_on_the_app_host(): void
    {
        // Legacy installs activated before domain was recorded.
        $legacy = License::create([
            'license_key' => 'LEGACY-KEY-XXXX-YYYY',
            'domain' => '',
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'modules' => ['shop'],
        ]);

        $this->assertSame($legacy->id, License::current()?->id);
    }

    public function test_domain_less_license_never_outranks_an_explicit_host_match(): void
    {
        $prod = $this->seedProductionLicense();
        License::create([
            'license_key' => 'LEGACY-KEY-XXXX-YYYY',
            'domain' => '',
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'modules' => ['shop'],
        ]);

        $this->assertSame($prod->id, License::current()?->id);
    }

    public function test_unknown_host_resolves_nothing(): void
    {
        $this->seedProductionLicense();
        $this->seedLocalhostLicense();

        $this->assertNull(License::current('evil.example'));
        $this->assertNull(License::current('localhost'));
    }

    public function test_empty_host_falls_back_to_newest_domain_qualified_row(): void
    {
        $this->seedProductionLicense();
        $local = $this->seedLocalhostLicense();

        // Console/bootstrap context: no request host → legacy latest()
        // behaviour over domain-qualified rows (validity stays isValid()'s
        // job, exactly as before this change).
        $this->assertSame($local->id, License::current('')?->id);
    }

    public function test_no_licenses_at_all_resolves_null(): void
    {
        $this->assertNull(License::current());
        $this->assertNull(License::current('customer-site.test'));
    }

    public function test_module_visibility_resolves_license_only_once_per_request_host(): void
    {
        $this->seedProductionLicense();
        DB::flushQueryLog();
        DB::enableQueryLog();

        self::assertTrue(SiteHelper::moduleEnabled('shop'));
        self::assertTrue(SiteHelper::moduleEnabled('blog'));
        self::assertFalse(SiteHelper::moduleEnabled('reviews'));

        $licenseQueries = array_filter(DB::getQueryLog(), fn (array $query): bool => str_contains(strtolower($query['query']), 'licenses'));
        DB::disableQueryLog();
        self::assertCount(1, $licenseQueries);
    }

    public function test_request_license_cache_never_crosses_request_objects(): void
    {
        self::assertFalse(SiteHelper::moduleEnabled('shop'));
        $this->seedProductionLicense();
        self::assertFalse(SiteHelper::moduleEnabled('shop'), 'the current request keeps a consistent entitlement snapshot');

        $nextRequest = Request::create('http://customer-site.test/next');
        $this->app->instance('request', $nextRequest);

        self::assertTrue(SiteHelper::moduleEnabled('shop'), 'a later request must resolve its own host-scoped license');
    }

    // ── 5. integration: tenant context + EnsureLicenseValid behavior ─────────

    public function test_tenant_context_resolves_the_matching_license_under_the_production_host(): void
    {
        $prod = $this->seedProductionLicense();
        $this->seedLocalhostLicense();

        $resolved = app(TenantContext::class)->current();

        $this->assertNotNull($resolved);
        $this->assertSame($prod->id, $resolved->id);
        $this->assertTrue($resolved->isValid());
    }

    public function test_license_middleware_denies_when_only_another_hosts_invalid_license_exists(): void
    {
        // The exact reported incident: only the invalid 127.0.0.1 license on
        // disk, request under customer-site.test → the middleware must find
        // NO host-matching license (the invalid localhost row must not be
        // resolved for this host) and redirect to the license page.
        $this->seedLocalhostLicense(['platform_valid' => false]);

        $admin = \App\Models\User::factory()->create(['is_admin' => true]);
        $response = $this->actingAs($admin)
            ->withHeaders(['Host' => 'customer-site.test'])
            ->get('/admin/dashboard');

        $response->assertRedirect(route('admin.license.index'));
        $response->assertSessionHas('error');
    }

    public function test_license_middleware_admits_the_matching_valid_license(): void
    {
        $this->seedProductionLicense();
        $this->seedLocalhostLicense(['platform_valid' => false]);

        $admin = \App\Models\User::factory()->create(['is_admin' => true]);
        $response = $this->actingAs($admin)
            ->withHeaders(['Host' => 'customer-site.test'])
            ->get('/admin/dashboard');

        // A tenant admin passes every downstream middleware, so HTTP 200
        // proves the request traveled THROUGH the license gate — i.e. the
        // valid host-matched license was resolved and accepted. (The old
        // latest() bug denied this exact request with a license redirect.)
        $response->assertOk();
    }
}

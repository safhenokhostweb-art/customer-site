<?php

namespace Tests\Feature;

use App\Models\License;
use App\Models\User;
use App\Services\LicenseActivationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Wave 5.4 — Regression tests for Wave 4 / P2-5 (marketplace owned/status
 * status rendering) and Wave 4 / P2-6 (transient license banner cache key
 * + LicenseActivationService::syncWithPlatform cache semantics).
 *
 * P2-5 — module-store status badge logic (Blade fallback):
 *     $status = $module['status'] ?? (
 *         !empty($module['installed']) ? 'installed'
 *         : (!empty($module['owned'])   ? 'owned_disabled'
 *                                       : 'available')
 *     );
 *   Three terminal states are rendered with three distinct badges/buttons:
 *     'installed'      → "نصب شده" badge
 *     'owned_disabled' → "قبلاً خریداری‌شده — غیرفعال" badge + "فعال‌سازی"
 *     'available'      → "خرید" button
 *   Backwards compatibility is required: if the API response omits
 *   `status`, the legacy `installed`/`owned` booleans must still resolve
 *   to the correct badge so existing platforms do not break.
 *
 * P2-6 — LicenseActivationService transient error cache:
 *   - transientErrorCacheKey($id) is a stable, license-scoped cache key
 *     in the form 'license.transient_error.{id}'.
 *   - On a transient sync failure (5xx response or connection_error),
 *     the cache key is set with the error payload AND the license is
 *     NOT marked invalid; the result reports sync_skipped=true.
 *   - On a successful sync, the cache key is forgotten (banner cleared)
 *     and platform_valid flips to true.
 *   - On a definite rejection (revoked / expired / invalid_key /
 *     domain_mismatch / site_inactive), the cache key is NOT set and
 *     platform_valid is updated to false (real revocation, not banner).
 */
class Wave4MarketplaceAndBannerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        // Skip EnsureLicenseValid + EnsureModuleEnabled by running in owner-site mode.
        config(['site.owner_site' => true]);
        config(['site.platform_url' => 'https://platform.example']);
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    private function setupLicensedAdmin(): User
    {
        License::create([
            'license_key' => 'STORE-KEY1-XXXX-YYYY',
            'domain' => request()->getHost(),
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => ['shop', 'blog'],
        ]);
        return User::factory()->create(['is_admin' => true]);
    }

    // =====================================================================
    // P2-5 — marketplace owned/status status rendering
    // =====================================================================

    public function test_marketplace_renders_three_statuses_distinctly_via_status_field(): void
    {
        $admin = $this->setupLicensedAdmin();

        Http::fake([
            'platform.example/api/tenant-marketplace/modules*' => Http::response([
                'allowed' => true,
                'platform_store_url' => 'https://platform.example/store/modules',
                'modules' => [
                    ['slug' => 'p25_blog', 'title' => 'وبلاگ', 'description' => 'd1', 'status' => 'installed',      'price_irr' => 0],
                    ['slug' => 'p25_shop', 'title' => 'فروشگاه', 'description' => 'd2', 'status' => 'owned_disabled', 'price_irr' => 1000000],
                    ['slug' => 'p25_buy',  'title' => 'کوپن',   'description' => 'd3', 'status' => 'available',     'price_irr' => 500000],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.module-store.index'));
        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('نصب شده', $html, 'installed badge missing');
        $this->assertStringContainsString('قبلاً خریداری‌شده — غیرفعال', $html, 'owned_disabled badge missing');
        $this->assertStringContainsString('فعال‌سازی', $html, 'activation link missing for owned_disabled');
        $this->assertStringContainsString('خرید', $html, 'buy button missing for available');
    }

    public function test_marketplace_backward_compat_when_status_field_missing(): void
    {
        $admin = $this->setupLicensedAdmin();

        Http::fake([
            'platform.example/api/tenant-marketplace/modules*' => Http::response([
                'allowed' => true,
                'modules' => [
                    // Legacy API: only `installed`/`owned` booleans, no `status`.
                    ['slug' => 'p25_legacy_inst',  'title' => 'لگاسی نصب‌شده',  'installed' => true,  'owned' => true,  'price_irr' => 1000000],
                    ['slug' => 'p25_legacy_owned', 'title' => 'لگاسی مالک',     'installed' => false, 'owned' => true,  'price_irr' => 1000000],
                    ['slug' => 'p25_legacy_avail', 'title' => 'لگاسی قابل خرید','installed' => false, 'owned' => false, 'price_irr' => 1500000],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.module-store.index'));
        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('نصب شده', $html);
        $this->assertStringContainsString('قبلاً خریداری‌شده — غیرفعال', $html);
        $this->assertStringContainsString('فعال‌سازی', $html);
        $this->assertStringContainsString('خرید', $html);
    }

    public function test_marketplace_does_not_render_buy_button_for_installed_module(): void
    {
        $admin = $this->setupLicensedAdmin();

        Http::fake([
            'platform.example/api/tenant-marketplace/modules*' => Http::response([
                'allowed' => true,
                'modules' => [
                    ['slug' => 'p25_only_installed', 'title' => 'فقط نصب‌شده', 'status' => 'installed', 'price_irr' => 0],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.module-store.index'));
        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('نصب شده', $html);
        $this->assertStringNotContainsString('قبلاً خریداری‌شده — غیرفعال', $html);
        // The "خرید" word must not appear in a module card for an installed item.
        // (It might appear elsewhere as static text — so we additionally check for the
        // anchor pattern used by the buy button.)
        $this->assertStringNotContainsString('btn btn-primary btn-sm', $html);
    }

    // =====================================================================
    // P2-6 — transient banner cache key + sync semantics
    // =====================================================================

    public function test_transient_error_cache_key_is_stable_and_license_scoped(): void
    {
        $this->assertSame('license.transient_error.1', LicenseActivationService::transientErrorCacheKey(1));
        $this->assertSame('license.transient_error.42', LicenseActivationService::transientErrorCacheKey(42));
        $this->assertNotSame(
            LicenseActivationService::transientErrorCacheKey(1),
            LicenseActivationService::transientErrorCacheKey(2),
        );
    }

    public function test_sync_with_platform_caches_transient_error_on_5xx(): void
    {
        Cache::flush();

        $license = License::create([
            'license_key' => 'TEST-KEY1-XXXX-YYYY',
            'domain' => 'p26.test',
            'platform_valid' => true,
        ]);

        Http::fake([
            'platform.example/api/license/validate' => Http::response(['message' => 'server is down'], 503),
        ]);

        $result = app(LicenseActivationService::class)->syncWithPlatform($license, 'p26.test');

        $this->assertTrue($result['valid'], 'transient 5xx must NOT mark license invalid');
        $this->assertTrue($result['sync_skipped'] ?? false);

        $cached = Cache::get(LicenseActivationService::transientErrorCacheKey($license->id));
        $this->assertNotNull($cached, 'transient banner cache key must be set on 5xx');
        $this->assertSame(503, $cached['http_status']);
        $this->assertArrayHasKey('detected_at', $cached);

        $reloaded = License::find($license->id);
        $this->assertTrue((bool) $reloaded->platform_valid, 'license must remain platform_valid=true on transient error');
    }

    public function test_sync_with_platform_caches_transient_error_on_connection_failure(): void
    {
        Cache::flush();

        $license = License::create([
            'license_key' => 'TEST-KEY2-XXXX-YYYY',
            'domain' => 'p26-net.test',
            'platform_valid' => true,
        ]);

        Http::fake([
            'platform.example/api/license/validate' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('connect timeout');
            },
        ]);

        $result = app(LicenseActivationService::class)->syncWithPlatform($license, 'p26-net.test');

        $this->assertTrue($result['valid'], 'connection error must NOT mark license invalid');
        $cached = Cache::get(LicenseActivationService::transientErrorCacheKey($license->id));
        $this->assertNotNull($cached);
        $this->assertSame('connection_error', $cached['error']);
    }

    public function test_sync_with_platform_clears_cache_on_successful_validation(): void
    {
        Cache::flush();

        $license = License::create([
            'license_key' => 'TEST-KEY3-XXXX-YYYY',
            'domain' => 'p26-ok.test',
            'platform_valid' => false,
        ]);

        Cache::put(
            LicenseActivationService::transientErrorCacheKey($license->id),
            ['error' => 'stale', 'detected_at' => now()->toIso8601String()],
            now()->addMinutes(30)
        );
        $this->assertNotNull(Cache::get(LicenseActivationService::transientErrorCacheKey($license->id)));

        Http::fake([
            'platform.example/api/license/validate' => Http::response([
                'valid' => true,
                'modules' => ['blog'],
                'support_level' => 'basic',
                'expires_at' => now()->addYear()->toIso8601String(),
            ], 200),
        ]);

        $result = app(LicenseActivationService::class)->syncWithPlatform($license, 'p26-ok.test');
        $this->assertTrue($result['valid']);

        $this->assertNull(
            Cache::get(LicenseActivationService::transientErrorCacheKey($license->id)),
            'successful sync must clear the transient banner cache'
        );

        $reloaded = License::find($license->id);
        $this->assertTrue((bool) $reloaded->platform_valid);
    }

    public function test_sync_with_platform_does_not_cache_on_definite_rejection(): void
    {
        Cache::flush();

        $license = License::create([
            'license_key' => 'TEST-KEY4-XXXX-YYYY',
            'domain' => 'p26-rev.test',
            'platform_valid' => true,
        ]);

        Http::fake([
            'platform.example/api/license/validate' => Http::response([
                'valid' => false,
                'error' => 'revoked',
            ], 200),
        ]);

        $result = app(LicenseActivationService::class)->syncWithPlatform($license, 'p26-rev.test');

        $this->assertFalse($result['valid']);
        $this->assertNull(
            Cache::get(LicenseActivationService::transientErrorCacheKey($license->id)),
            'definite rejection must NOT populate the transient banner cache'
        );

        $reloaded = License::find($license->id);
        $this->assertFalse((bool) $reloaded->platform_valid);
    }
}

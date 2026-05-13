<?php

namespace Tests\Concerns;

use App\Models\License;

/**
 * Wave 6.2 — Reusable test helper for any feature test that hits routes
 * gated by `module:shop`, `module:blog`, or `license.valid` middleware.
 *
 * Why this trait exists:
 *   - `App\Helpers\SiteHelper::moduleEnabled('shop')` (and `'blog'`) is the
 *     license-only gate (no .env fallback for those two modules). Without a
 *     valid License row whose `domain` matches `request()->getHost()`, the
 *     gate returns `false` and `EnsureModuleEnabled` aborts with HTTP 403.
 *   - `App\Models\License::isValid()` compares the seeded `domain` against
 *     `request()->getHost()`, which in PHPUnit derives from `APP_URL` (set
 *     in `.env` / `.env.testing`). Local dev `.env` usually points to a
 *     non-`localhost` host (e.g. `customer-site.test`), so the legacy
 *     hardcoded `domain => 'localhost'` seed silently broke.
 *
 * Usage:
 *   class MyFeatureTest extends TestCase
 *   {
 *       use RefreshDatabase, SeedsValidLicenseWithShop;
 *
 *       protected function setUp(): void
 *       {
 *           parent::setUp();
 *           $this->markSiteAsInstalled();
 *           $this->seedValidLicense(['shop']);          // or ['shop','blog','forms',…]
 *       }
 *   }
 *
 * Production logic is NOT touched by this trait; it only seeds the test
 * environment so it faithfully reflects an installed, licensed tenant.
 */
trait SeedsValidLicenseWithShop
{
    /**
     * Seed (or reuse) a valid License row for the current test request host.
     *
     * - Host is resolved from `config('app.url')`; this matches the value
     *   PHPUnit uses as the default request host.
     * - `firstOrCreate` keyed on `domain` so multiple invocations in the
     *   same test (or by multiple traits) never create duplicate licenses.
     * - `last_validated_at` set to now() so `EnsureLicenseValid` does not
     *   trigger a real `syncWithPlatform` HTTP call.
     *
     * @param  list<string>  $modules  Module slugs to enable. Defaults to
     *                                 ['shop'] for ShopModuleFeatureTest;
     *                                 callers may pass ['shop','blog','forms',…]
     *                                 to test multi-module flows.
     */
    protected function seedValidLicense(array $modules = ['shop']): License
    {
        $host = $this->testRequestHost();

        return License::query()->firstOrCreate(
            ['domain' => $host],
            [
                'license_key' => 'W62-' . uniqid(),
                'expires_at' => now()->addYear(),
                'modules' => $modules,
                'platform_valid' => true,
                'last_validated_at' => now(),
            ]
        );
    }

    /**
     * Ensure `storage/installed.lock` is present so `RedirectIfNotInstalled`
     * does not redirect Shop/Blog/etc. routes to the installer wizard.
     *
     * The file is tracked in the repository, so we never delete it on
     * tearDown — that would corrupt the developer's working tree. We only
     * create it if it is missing (covers fresh CI checkouts that exclude
     * tracked storage files).
     */
    protected function markSiteAsInstalled(): void
    {
        $path = storage_path('installed.lock');
        if (! file_exists($path)) {
            file_put_contents($path, '1');
        }
    }

    /**
     * The request host PHPUnit will use as the default Host header — same
     * resolution as `License::isValid()` does via `request()->getHost()`
     * once a request runs, but available *before* any HTTP call so the
     * seed value matches the eventual request.
     */
    protected function testRequestHost(): string
    {
        $appUrl = (string) config('app.url', 'http://localhost');
        $host = parse_url($appUrl, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : 'localhost';
    }
}

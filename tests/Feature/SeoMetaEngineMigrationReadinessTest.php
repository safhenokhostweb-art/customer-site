<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\SeoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SaaSPlatform\CoreUtils\Contracts\Services\SeoOverrideProviderInterface;
use SaaSPlatform\Modules\SeoAdvanced\Adapters\SeoOverrideProviderAdapter;
use SaaSPlatform\Modules\SeoAdvanced\Models\SeoAdvancedSetting;
use Tests\Concerns\EnsuresInstallationLock;
use Tests\TestCase;

/**
 * Migration-readiness gate for porting the saas-platform SEO MetaEngine work
 * into customer-site.
 *
 * ── What the port is actually moving ────────────────────────────────────────
 * `saas-platform/core-utils` is consumed by customer-site as a Composer *path*
 * repository (`../saas-platform/packages/saas-platform/core-utils`, options
 * `symlink: false`), installed as `dev-main` and copied into
 * vendor/saas-platform/core-utils. It is NOT declared in customer-site's own
 * `require`; it arrives transitively through `saas-platform/modules-seo-advanced`,
 * which requires `saas-platform/core-utils: @dev`.
 *
 * The vendored copy ships the SEO package (`src/SEO/MetaEngine.php`,
 * `MetaContext.php`, `MetaData.php`) alongside the override contract. It is a
 * Composer *copy* of the path repository, so it lags upstream until
 * `composer reinstall saas-platform/core-utils` is run — which is why the gate
 * below asserts the mirror is current instead of assuming it.
 *
 * ─── What the refresh does and does not change ────────────────────────────
 * customer-site resolves metadata with its OWN `App\Services\SeoService`, not
 * with MetaEngine. It already reads the module's camelCase `ogImage` /
 * `twitterCard` keys (App\Services\SeoService::imageUrl() and ::twitterCard()),
 * so the `ogImage` alias fix inside `MetaEngine` has no runtime effect here —
 * and does not need one. What the refresh DOES change is availability: the
 * MetaEngine classes are now loadable, so the two runtimes can converge on one
 * resolver instead of two when that is done deliberately.
 *
 * This test therefore proves both halves separately, and fails loudly if the
 * second one regresses:
 *   (a) the adapter is resolvable and reachable through customer-site's
 *       SeoService;
 *   (b) the module's ogImage / twitterCard reach a REAL rendered page.
 */
class SeoMetaEngineMigrationReadinessTest extends TestCase
{
    use EnsuresInstallationLock, RefreshDatabase;

    private const OG_IMAGE = 'https://cdn.example.invalid/seo-advanced-og.png';

    /**
     * The public-page tests below assert HTTP 200. Without the lock the
     * `redirect.if.not.installed` middleware answers 302 to the installer, so
     * a missing lock surfaces as a routing-looking failure rather than a
     * missing fixture.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureInstallationLock();
    }

    protected function tearDown(): void
    {
        $this->releaseInstallationLock();
        parent::tearDown();
    }

    private function moduleSettings(array $overrides = []): SeoAdvancedSetting
    {
        // site_id 1 is the module's own single-tenant constant — see
        // SeoOverrideProviderAdapter::SINGLE_TENANT_SITE_ID.
        return SeoAdvancedSetting::create(array_merge([
            'site_id' => 1,
            'meta_title' => 'عنوان ماژول سئو',
            'meta_description' => 'توضیح ماژول سئو برای تست مهاجرت.',
            'robots_directive' => 'index, follow',
            'og_image_url' => self::OG_IMAGE,
            'twitter_card_type' => 'summary_large_image',
        ], $overrides));
    }

    // ── (a) is the adapter identifiable by customer-site's SeoService? ──────

    public function test_the_seo_override_contract_resolves_to_the_seo_advanced_adapter(): void
    {
        $this->assertTrue(
            app()->bound(SeoOverrideProviderInterface::class),
            'SeoOverrideProviderInterface must always be bound, by a module or by the host default.'
        );

        $this->assertInstanceOf(
            SeoOverrideProviderAdapter::class,
            app(SeoOverrideProviderInterface::class),
            'An installed SEO Advanced package must win the contract. AppServiceProvider uses '
            . 'bindIf (not bind) precisely so this survives package-provider registration order.'
        );
    }

    public function test_customer_site_resolves_metadata_with_its_own_service_not_the_shared_engine(): void
    {
        // customer-site still has a second, independent resolver: app(SeoService::class)
        // is NOT the shared MetaEngine. The path package is now refreshed, so
        // MetaEngine is loadable alongside it — availability, not adoption. The
        // two are pinned to the same observable contract by the tests below, so
        // swapping SeoService over to MetaEngine stays a behaviour-neutral change.
        $this->assertInstanceOf(SeoService::class, app(SeoService::class));

        $this->assertTrue(
            class_exists(\SaaSPlatform\CoreUtils\SEO\MetaEngine::class),
            'After `composer reinstall saas-platform/core-utils` the vendored copy must ship '
            . 'src/SEO, so the shared MetaEngine is loadable here. If this fails, re-run the '
            . 'reinstall; do NOT reintroduce a stale vendor copy.'
        );
    }

    public function test_customer_site_seo_service_reads_the_module_og_image_through_the_adapter(): void
    {
        $this->moduleSettings();

        $seo = app(SeoService::class);

        // imageUrl() probes the provider's `ogImage` key — the exact camelCase
        // spelling SeoOverrideProviderInterface declares and the adapter emits.
        $this->assertSame(self::OG_IMAGE, $seo->imageUrl());
        $this->assertSame('summary_large_image', $seo->twitterCard());
    }

    // ── (b) does the module's ogImage reach a real page? ────────────────────

    public function test_a_real_public_page_renders_the_module_og_image_and_twitter_card(): void
    {
        $this->activateMinimalCanvasTheme();
        $this->moduleSettings();

        $response = $this->get('/');

        $response->assertOk();
        $html = (string) $response->getContent();

        $this->assertStringContainsString(
            '<meta property="og:image" content="' . self::OG_IMAGE . '">',
            $html,
            'the module og_image_url must reach the rendered public page'
        );
        $this->assertStringContainsString(
            '<meta name="twitter:card" content="summary_large_image">',
            $html,
            'the module twitter_card_type must reach the rendered public page'
        );
        $this->assertStringContainsString(
            '<meta name="twitter:image" content="' . self::OG_IMAGE . '">',
            $html
        );

        // The migration must not introduce a second renderer either.
        $this->assertSame(1, preg_match_all('/<title\b/i', $html));
        $this->assertSame(1, preg_match_all('/<meta\s+name="description"/i', $html));
        $this->assertSame(1, preg_match_all('/<link\s+rel="canonical"/i', $html));
        $this->assertSame(1, preg_match_all('/<meta\s+name="robots"/i', $html));
        $this->assertSame(1, preg_match_all('/<meta\s+property="og:image"/i', $html));
    }

    public function test_a_page_level_og_image_outranks_the_module_default(): void
    {
        $this->activateMinimalCanvasTheme();
        $this->moduleSettings();

        $page = [
            'title' => 'صفحهٔ آزمایشی',
            'og_image' => 'https://cdn.example.invalid/page-level.png',
            'blocks' => [],
        ];

        $meta = app(SeoService::class)->canvasPage($page, []);

        $this->assertSame('https://cdn.example.invalid/page-level.png', $meta['image']);
    }

    // ── the migration gate itself ───────────────────────────────────────────

    public function test_the_vendored_core_utils_copy_ships_the_seo_package(): void
    {
        $vendoredSeo = base_path('vendor/saas-platform/core-utils/src/SEO');

        // The vendored copy is a Composer *path* mirror (symlink: false), so it
        // only picks up upstream work when reinstalled. This is the regression
        // guard for that: a stale mirror reads as "not ported" and silently
        // reintroduces the two-resolver divergence.
        $this->assertDirectoryExists(
            $vendoredSeo,
            'vendor/saas-platform/core-utils/src/SEO is missing. Run '
            . '`composer reinstall saas-platform/core-utils` followed by `composer dump-autoload` '
            . 'in customer-site — the path repository is copied, not symlinked.'
        );

        foreach (['MetaContext', 'MetaData', 'MetaEngine'] as $class) {
            $this->assertFileExists(
                $vendoredSeo.DIRECTORY_SEPARATOR.$class.'.php',
                'src/SEO is present but incomplete: '.$class.'.php did not mirror across.'
            );
        }

        // class_exists() returns false for an interface; interface_exists() is
        // the correct probe for the contract.
        $this->assertTrue(
            interface_exists(SeoOverrideProviderInterface::class),
            'The override contract must be present in the vendored copy.'
        );
    }

    public function test_the_shared_engine_becomes_loadable_once_the_path_package_is_refreshed(): void
    {
        if (! class_exists(\SaaSPlatform\CoreUtils\SEO\MetaEngine::class)) {
            $this->markTestSkipped(
                'SaaSPlatform\\CoreUtils\\SEO\\MetaEngine is not loadable: the path package in '
                . 'vendor/saas-platform/core-utils predates src/SEO. Refresh it with '
                . '`composer reinstall saas-platform/core-utils` (a plain `composer update` is a '
                . 'no-op here — the lock reference is sha1(package composer.json + repo options), '
                . 'which source-file changes do not affect).'
            );
        }

        $this->assertTrue(class_exists(\SaaSPlatform\CoreUtils\SEO\MetaContext::class));
        $this->assertTrue(class_exists(\SaaSPlatform\CoreUtils\SEO\MetaData::class));

        // The ogImage/twitterCard aliases the port added must survive the copy.
        $metaData = (new \SaaSPlatform\CoreUtils\SEO\MetaEngine())->resolve(
            \SaaSPlatform\CoreUtils\SEO\MetaContext::TENANT_PUBLIC,
            source: null,
            providerDefaults: [
                'ogImage' => self::OG_IMAGE,
                'twitterCard' => 'summary_large_image',
            ],
        );

        $this->assertSame(self::OG_IMAGE, $metaData->og_image);
        $this->assertSame('summary_large_image', $metaData->twitter_card);
    }

    // ── fixture ─────────────────────────────────────────────────────────────

    private function activateMinimalCanvasTheme(): void
    {
        // `GET /` is ThemeCanvasPageController@index, which delegates to
        // HomeController whenever active_canvas_theme_data is empty. Seeding a
        // canvas theme is therefore what routes the response through
        // layouts/canvas-theme.blade.php — the layout that actually emits
        // <x-seo-meta> for a public tenant page.
        Setting::set('active_canvas_theme_name', 'migration-probe', 'theme');
        Setting::set('active_canvas_theme_data', json_encode([
            'name' => 'migration-probe',
            'pages' => [
                'home' => [
                    'title' => 'خانهٔ تست مهاجرت',
                    'blocks' => [
                        ['id' => 'b1', 'type' => 'text', 'props' => ['content' => 'متن نمونهٔ صفحهٔ اصلی.']],
                    ],
                ],
            ],
            'header_blocks' => [],
            'footer_blocks' => [],
            'global_styles' => [],
        ]), 'theme');
    }
}

<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\License;
use App\Models\Setting;
use App\Models\ThemeInstanceRevision;
use App\Models\User;
use App\Services\BuilderLayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Universal Commercial Theme (TASK 3/3), Phase 4 — real install acceptance.
 *
 * `ThemeInstallRoundTripTest` already proves the install mechanism itself
 * (real route, real 'provision.secret' middleware, real controller, real
 * ThemeInstanceRevisionService, real migrated DB) using a small synthetic
 * 3-page/1-header/1-footer payload. What it does not prove is that the
 * ACTUAL universal-multipurpose-qa product — 24 real pages, 6 real header
 * variants, 6 real footer variants, as saas-platform's own seeder produces
 * them — survives the same canonical path intact, and that variant
 * selection has real behavioral effect after install (not just data
 * presence). This test closes that gap using a fixture exported from a
 * real run of `UniversalMultipurposeQaThemeSeeder` (see
 * fixtures/universal-multipurpose-qa-export.json), not hand-typed sample
 * data — the same shape saas-platform's `ThemeInstallGateway` actually
 * sends (verified against saas-platform's own
 * ThemeCanvasInstallToSiteHttpTest, which asserts this exact payload shape
 * over the wire).
 */
class RealUniversalThemeInstallAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        config([
            'site.owner_site' => true,
            'services.customer_provision_secret' => 'umq-acceptance-test-secret',
        ]);
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    private function admin(): User
    {
        License::create([
            'license_key' => 'UMQ-ACCEPT-' . uniqid(),
            'domain' => request()->getHost(),
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => ['shop', 'blog'],
        ]);

        return User::factory()->create(['is_admin' => true]);
    }

    /** The real, seeder-produced universal-multipurpose-qa export. */
    private function realThemeExport(): array
    {
        $path = __DIR__ . '/fixtures/universal-multipurpose-qa-export.json';
        $decoded = json_decode(file_get_contents($path), true);
        self::assertIsArray($decoded, 'fixture must be valid JSON');
        return $decoded;
    }

    private function installPayload(): array
    {
        $export = $this->realThemeExport();

        return [
            'theme_id' => 'universal-multipurpose-qa',
            'theme_name' => 'قالب جامع چندمنظوره',
            'source_theme_id' => '1',
            'source_theme_version' => '1.0.0',
            'source_release_id' => 99,
            'header_blocks' => $export['header_blocks'],
            'footer_blocks' => $export['footer_blocks'],
            'header_variants' => $export['header_variants'],
            'footer_variants' => $export['footer_variants'],
            'pages' => $export['pages'],
            'global_styles' => $export['global_styles'],
            'extension_assets' => [],
        ];
    }

    private function callInstall(array $payload): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders(['X-Provision-Secret' => 'umq-acceptance-test-secret'])
            ->postJson(route('builder.api.theme.install'), $payload);
    }

    // ── 1/2/3. Real 24-page product survives the canonical install path ────

    public function test_all_24_real_pages_survive_the_canonical_install_path(): void
    {
        $response = $this->callInstall($this->installPayload());

        $response->assertOk();
        $response->assertJson(['success' => true, 'pages_installed' => 24]);

        $revision = ThemeInstanceRevision::where('theme_identifier', 'universal-multipurpose-qa')->sole();
        self::assertSame(ThemeInstanceRevision::TYPE_INSTALL, $revision->type);
        self::assertSame('1.0.0', $revision->source_theme_version);
        self::assertSame(99, $revision->source_release_id);
        self::assertCount(24, $revision->theme_data['pages']);

        $activeData = json_decode(Setting::get('active_canvas_theme_data'), true);
        self::assertCount(24, $activeData['pages']);

        // Every page from Phase 2's category-specific homepage compositions
        // is present by key, not silently dropped or renamed in transit.
        foreach (['home', 'home_service', 'home_ecommerce', 'home_personal', 'home_magazine', 'home_education'] as $key) {
            self::assertArrayHasKey($key, $activeData['pages'], "page '{$key}' must survive install");
        }
    }

    // ── 4. Header/footer variants survive with real counts and content ─────

    public function test_all_6_header_and_6_footer_variants_survive_with_real_blocks(): void
    {
        $this->callInstall($this->installPayload())->assertOk();

        $activeData = json_decode(Setting::get('active_canvas_theme_data'), true);
        self::assertCount(6, $activeData['header_variants']);
        self::assertCount(6, $activeData['footer_variants']);
        foreach ($activeData['header_variants'] as $variant) {
            self::assertNotEmpty($variant['blocks'], "header variant '{$variant['key']}' must carry real blocks");
        }
        foreach ($activeData['footer_variants'] as $variant) {
            self::assertNotEmpty($variant['blocks'], "footer variant '{$variant['key']}' must carry real blocks");
        }

        // BuilderLayoutService (the real runtime consumer every front page
        // renders through) resolves the exact same 6/6 from the just-
        // installed active_canvas_theme_data — not a second, independent
        // read path.
        $layout = app(BuilderLayoutService::class);
        self::assertCount(6, $layout->getHeaderVariants());
        self::assertCount(6, $layout->getFooterVariants());
    }

    // ── 5. Public render: original home AND a new Phase-2 composition ──────

    public function test_public_render_works_for_original_home_and_a_new_homepage_composition(): void
    {
        $this->callInstall($this->installPayload())->assertOk();

        $home = $this->get('/');
        $home->assertOk();
        $home->assertSee('راهکارهای مدرن برای کسب‌وکارهای ایرانی');

        // A page that did not exist before Phase 2 of this task — proves
        // the install path isn't only tested against legacy content.
        $service = $this->get('/page/home-service');
        $service->assertOk();
        $service->assertSee('سلامت شما، اولویت اول ماست');

        $ecommerce = $this->get('/page/home-ecommerce');
        $ecommerce->assertOk();
        $ecommerce->assertSee('کلکسیون جدید دیجی‌مد رسید');
    }

    // ── 6. Variant selection has real behavioral effect after install ──────

    public function test_selecting_a_non_default_header_variant_after_install_actually_changes_public_render(): void
    {
        $this->callInstall($this->installPayload())->assertOk();

        // Default variant ('classic') has no search_bar/cart_icon — confirm
        // the baseline first so the switch below is a real A/B, not an
        // assumption.
        $before = $this->get('/');
        $before->assertOk();
        // The element, not the class name: the shared stylesheet styles
        // .tb-search-bar-wrap on every page (Phase 0.7, ADR-066).
        $before->assertDontSee('class="tb-search-bar-wrap"', false);

        // Switch the site's default header to 'ecommerce_search' (search_bar
        // + nav_header + cart_icon) the same way an admin would from
        // Settings — through the real Setting key BuilderLayoutService reads.
        Setting::set(BuilderLayoutService::SETTING_HEADER_TEMPLATE, 'ecommerce_search', 'theme');

        $after = $this->get('/');
        $after->assertOk();
        $after->assertSee('class="tb-search-bar-wrap"', false);
    }

    // ── 6b. Performance gate: variant resolution adds no unbounded query cost ──

    public function test_header_footer_variant_resolution_does_not_add_unbounded_queries(): void
    {
        $this->callInstall($this->installPayload())->assertOk();

        // Warm any one-time caches (route/view compilation, settings cache)
        // so the measured request reflects steady-state cost, not first-hit
        // warmup — the same convention CustomerMediaServiceTest already uses.
        $this->get('/')->assertOk();

        DB::enableQueryLog();
        $this->get('/')->assertOk();
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Observed steady-state cost is 12 queries for a full home-page
        // render with 6 header + 6 footer variants installed; 20 leaves
        // headroom for legitimate unrelated query variance while still
        // catching the specific failure mode this test exists to prevent —
        // a per-variant query (would scale linearly with variant count).
        self::assertLessThanOrEqual(
            20,
            $queryCount,
            "Public render with 6 header/6 footer variants must not scale query count with variant count (got {$queryCount} queries)."
        );
    }

    // ── 7. Customer instance ownership: no shadow Page rows, no cross-site leak ─

    public function test_installed_pages_do_not_create_real_page_rows_that_could_collide(): void
    {
        $this->callInstall($this->installPayload())->assertOk();

        self::assertSame(
            0,
            \App\Models\Page::whereIn('slug', ['about', 'contact', 'shop', 'home-service', 'home-ecommerce'])->count(),
            'canvas-owned page slugs must never materialize as real Page rows (see ThemeInstallRoundTripTest for the collision-guard proof)'
        );
    }

    // ── 8. Normal Canvas save/publish after installing the REAL product ────

    public function test_normal_save_publish_succeeds_after_installing_the_real_product(): void
    {
        $this->callInstall($this->installPayload())->assertOk();
        $admin = $this->admin();

        $activeData = json_decode(Setting::get('active_canvas_theme_data'), true);
        $activeData['pages']['about']['blocks'][] = ['type' => 'text', 'props' => ['content' => 'ویرایش واقعی مشتری پس از نصب محصول واقعی']];
        Setting::set('canvas_draft_data', json_encode($activeData, JSON_UNESCAPED_UNICODE), 'theme');

        $publish = $this->actingAs($admin)->postJson(route('admin.theme-design.canvas-publish'));
        $publish->assertOk();
        $publish->assertJson(['success' => true]);

        $revisions = ThemeInstanceRevision::where('theme_identifier', 'universal-multipurpose-qa')
            ->orderBy('revision_number')->get();
        self::assertCount(2, $revisions, 'publish after installing the real 24-page product must add a revision cleanly');
        self::assertSame(24, count($revisions[1]->theme_data['pages']), 'publish must not drop pages from the real product');

        $about = $this->get('/page/about');
        $about->assertOk();
        $about->assertSee('ویرایش واقعی مشتری پس از نصب محصول واقعی');
    }
}

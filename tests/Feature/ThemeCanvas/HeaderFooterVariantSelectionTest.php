<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\License;
use App\Models\Page;
use App\Models\User;
use App\Services\BuilderLayoutService;
use App\Services\ThemeCanvas\ThemeInstanceRevisionService;
use App\Models\ThemeInstanceRevision;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsValidLicenseWithShop;
use Tests\TestCase;

/**
 * THEME-03 (Full Product Commercial Audit, TASK 2A): ONE canonical
 * Header/Footer authoring authority — the installed Theme's own variants
 * (`active_canvas_theme_data.header_variants`/`footer_variants`). Page-level
 * overrides store only a stable variant `key` reference
 * (`builder_header_template_override`), never literal content.
 * `config/builder-headers.php`/`builder-footers.php` are no longer read by
 * BuilderLayoutService at all — proven below, not assumed.
 */
class HeaderFooterVariantSelectionTest extends TestCase
{
    use RefreshDatabase, SeedsValidLicenseWithShop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markSiteAsInstalled();
    }

    private function admin(): User
    {
        $this->seedValidLicense(['pages']);
        return User::factory()->create(['is_admin' => true]);
    }

    private function installThemeWithVariants(): void
    {
        app(ThemeInstanceRevisionService::class)->record([
            'header_blocks' => [['id' => 'h_default', 'type' => 'text', 'props' => ['content' => 'HEADER-DEFAULT-MARKER']]],
            'footer_blocks' => [['id' => 'f_default', 'type' => 'text', 'props' => ['content' => 'FOOTER-DEFAULT-MARKER']]],
            'header_variants' => [
                ['key' => 'default', 'label' => 'پیش‌فرض', 'blocks' => [['id' => 'h_default', 'type' => 'text', 'props' => ['content' => 'HEADER-DEFAULT-MARKER']]], 'is_default' => true],
                ['key' => 'minimal', 'label' => 'مینیمال', 'blocks' => [['id' => 'h_min', 'type' => 'text', 'props' => ['content' => 'HEADER-MINIMAL-MARKER']]], 'is_default' => false],
            ],
            'footer_variants' => [
                ['key' => 'default', 'label' => 'پیش‌فرض', 'blocks' => [['id' => 'f_default', 'type' => 'text', 'props' => ['content' => 'FOOTER-DEFAULT-MARKER']]], 'is_default' => true],
                ['key' => 'rich', 'label' => 'کامل', 'blocks' => [['id' => 'f_rich', 'type' => 'text', 'props' => ['content' => 'FOOTER-RICH-MARKER']]], 'is_default' => false],
            ],
            'pages' => [],
        ], ThemeInstanceRevision::TYPE_INSTALL, ['theme_identifier' => 'theme-03-test-' . uniqid()]);
    }

    // ── Data model: Theme contains multiple real variants ────────────────────

    public function test_theme_contains_multiple_header_and_footer_variants(): void
    {
        $this->installThemeWithVariants();
        $service = app(BuilderLayoutService::class);

        $this->assertCount(2, $service->getHeaderVariants());
        $this->assertCount(2, $service->getFooterVariants());
    }

    // ── Page selects a variant; save → reload → public render ────────────────

    public function test_page_level_header_variant_selection_changes_public_render(): void
    {
        $this->installThemeWithVariants();
        $page = Page::create([
            'title' => 'صفحه تست', 'slug' => 'theme03-page-' . uniqid(), 'body' => '<p>x</p>',
            'is_published' => true, 'show_in_menu' => false,
        ]);

        // Default: no override → default variant's header shows.
        $response = $this->get(route('page.show', $page->slug));
        $response->assertOk();
        $response->assertSee('HEADER-DEFAULT-MARKER', false);
        $response->assertDontSee('HEADER-MINIMAL-MARKER', false);

        // Author selects the 'minimal' variant via the real settings API —
        // save → reload → public render, per the required behavioral chain.
        $admin = $this->admin();
        $this->actingAs($admin)->putJson(route('builder.api.page.settings', $page), [
            'builder_header_override' => 'minimal',
        ])->assertOk();

        $page->refresh();
        $this->assertSame('minimal', $page->builder_header_template_override);

        $response = $this->get(route('page.show', $page->slug));
        $response->assertOk();
        $response->assertSee('HEADER-MINIMAL-MARKER', false);
        $response->assertDontSee('HEADER-DEFAULT-MARKER', false);
    }

    public function test_page_level_footer_variant_selection_changes_public_render(): void
    {
        $this->installThemeWithVariants();
        $page = Page::create([
            'title' => 'صفحه فوتر', 'slug' => 'theme03-footer-' . uniqid(), 'body' => '<p>x</p>',
            'is_published' => true, 'show_in_menu' => false,
        ]);

        $admin = $this->admin();
        $this->actingAs($admin)->putJson(route('builder.api.page.settings', $page), [
            'builder_footer_override' => 'rich',
        ])->assertOk();

        $response = $this->get(route('page.show', $page->slug));
        $response->assertOk();
        $response->assertSee('FOOTER-RICH-MARKER', false);
        $response->assertDontSee('FOOTER-DEFAULT-MARKER', false);
    }

    // ── Default fallback ──────────────────────────────────────────────────────

    public function test_no_override_falls_back_to_the_themes_default_variant(): void
    {
        $this->installThemeWithVariants();
        $service = app(BuilderLayoutService::class);

        $blocks = $service->getHeaderBlocks(null);
        $this->assertSame('HEADER-DEFAULT-MARKER', $blocks[0]['props']['content']);
    }

    // ── Stale/missing reference fails safely ─────────────────────────────────

    public function test_stale_variant_reference_fails_safe_to_default_not_an_exception(): void
    {
        $this->installThemeWithVariants();
        $page = Page::create([
            'title' => 'صفحه قدیمی', 'slug' => 'theme03-stale-' . uniqid(), 'body' => '<p>x</p>',
            'is_published' => true, 'show_in_menu' => false,
            'builder_header_template_override' => 'a-variant-that-no-longer-exists',
        ]);

        $service = app(BuilderLayoutService::class);
        $blocks = $service->getHeaderBlocks($page->fresh());

        $this->assertSame('HEADER-DEFAULT-MARKER', $blocks[0]['props']['content'], 'a stale key must fail safe to the default variant, not error or render blank');
    }

    public function test_no_theme_installed_does_not_crash_and_returns_no_variants(): void
    {
        // No installThemeWithVariants() call — simulates a fresh site.
        $service = app(BuilderLayoutService::class);
        $this->assertSame([], $service->getHeaderVariants());
        $this->assertIsArray($service->getHeaderBlocks(null));
    }

    // ── No second content authority remains ──────────────────────────────────

    public function test_static_preset_config_is_never_read_by_the_layout_service(): void
    {
        $this->installThemeWithVariants();
        // Poison the static config with a value that would only appear if
        // BuilderLayoutService still read it — proves a real absence of
        // the dependency, not just "happens not to be called" today.
        config(['builder-headers.templates' => [
            'default' => ['name' => 'STATIC-CONFIG-POISON', 'blocks' => [['id' => 'poison', 'type' => 'text', 'props' => ['content' => 'STATIC-CONFIG-POISON-MARKER']]]],
        ]]);
        config(['builder-footers.templates' => [
            'default' => ['name' => 'STATIC-CONFIG-POISON', 'blocks' => [['id' => 'poison', 'type' => 'text', 'props' => ['content' => 'STATIC-CONFIG-POISON-MARKER']]]],
        ]]);

        $service = app(BuilderLayoutService::class);
        $headerBlocks = $service->getHeaderBlocks(null);
        $footerBlocks = $service->getFooterBlocks(null);

        $this->assertSame('HEADER-DEFAULT-MARKER', $headerBlocks[0]['props']['content']);
        $this->assertSame('FOOTER-DEFAULT-MARKER', $footerBlocks[0]['props']['content']);
        $this->assertStringNotContainsString('STATIC-CONFIG-POISON', json_encode($headerBlocks));
        $this->assertStringNotContainsString('STATIC-CONFIG-POISON', json_encode($footerBlocks));
    }

    public function test_page_options_list_theme_owned_variants_not_static_config(): void
    {
        $this->installThemeWithVariants();
        config(['builder-headers.templates' => ['poison' => ['name' => 'STATIC-CONFIG-POISON']]]);

        $service = app(BuilderLayoutService::class);
        $options = $service->getHeaderOptionsForPage();

        $this->assertArrayHasKey('minimal', $options);
        $this->assertArrayNotHasKey('poison', $options);
    }
}

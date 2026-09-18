<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsValidLicenseWithShop;
use Tests\TestCase;

/**
 * TASK 3/3 Phase 6.5B — Menu <-> Header convergence (BLD-22).
 *
 * The Menu "add existing Page" picker (Phase D, see MenuPagePickerTest)
 * already worked — the actual unresolved gap was one level up: whether the
 * customer's real public header/footer navigation was backed by that Menu
 * system at all. A theme's `nav_header` block (used by the Universal Theme
 * and most canonical themes) maintained its OWN separate `nav_links`
 * label+url repeater, completely disconnected from /admin/menus — editing
 * a Menu had zero effect on what a visitor actually saw in the header.
 *
 * This proves the fix: nav_header's new `nav_source=menu` prop makes the
 * SAME real Menu (location=primary) the header's nav_source, through the
 * SAME resolver (`MenuItem::resolveUrl()`) MenuPagePickerTest already
 * proved survives a Page slug rename — end to end, on the real public
 * render, not the service in isolation.
 */
class HeaderMenuIntegrationTest extends TestCase
{
    use RefreshDatabase, SeedsValidLicenseWithShop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markSiteAsInstalled();
    }

    private function admin(): User
    {
        $this->seedValidLicense(['menu']);
        return User::factory()->create(['is_admin' => true]);
    }

    private function installThemeWithNavHeader(string $navSource = 'menu'): void
    {
        Setting::set('active_canvas_theme_data', json_encode([
            'name' => 'Test Theme', 'slug' => 'test-theme',
            'header_blocks' => [
                ['id' => 'hdr1', 'type' => 'nav_header', 'props' => [
                    'logo_text' => 'فروشگاه من', 'nav_source' => $navSource,
                    'nav_links' => [['label' => 'لینک سفارشی قدیمی', 'url' => '/old-custom-link']],
                ]],
            ],
            'footer_blocks' => [],
            'pages' => [['key' => 'home', 'title' => 'Home', 'blocks' => [
                ['id' => 'b1', 'type' => 'heading', 'props' => ['text' => 'خانه']],
            ]]],
            'global_styles' => [],
        ], JSON_UNESCAPED_UNICODE), 'theme');
        Setting::set('active_canvas_theme_name', 'Test Theme', 'theme');
    }

    public function test_nav_source_menu_renders_real_menu_items_on_the_public_header_not_the_blocks_own_nav_links(): void
    {
        $page = Page::create(['title' => 'درباره ما', 'slug' => 'about-us', 'body' => '<p>x</p>', 'is_published' => true]);
        $menu = Menu::create(['name' => 'اصلی', 'location' => 'primary', 'site_id' => null]);
        MenuItem::create([
            'menu_id' => $menu->id, 'title' => 'درباره ما',
            'type' => MenuItem::TYPE_INTERNAL_PAGE, 'reference_id' => $page->id, 'order' => 0,
        ]);

        $this->installThemeWithNavHeader('menu');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('درباره ما');
        $response->assertSee(route('page.show', 'about-us'), false);
        // The block's own nav_links must be ignored while nav_source=menu.
        $response->assertDontSee('لینک سفارشی قدیمی');
        $response->assertDontSee('/old-custom-link', false);
    }

    public function test_page_slug_rename_survives_in_the_real_rendered_header_link(): void
    {
        $page = Page::create(['title' => 'راهنما', 'slug' => 'guide-v1', 'body' => '<p>x</p>', 'is_published' => true]);
        $menu = Menu::create(['name' => 'اصلی', 'location' => 'primary', 'site_id' => null]);
        MenuItem::create([
            'menu_id' => $menu->id, 'title' => 'راهنما',
            'type' => MenuItem::TYPE_INTERNAL_PAGE, 'reference_id' => $page->id, 'order' => 0,
        ]);
        $this->installThemeWithNavHeader('menu');

        $this->get('/')->assertSee(route('page.show', 'guide-v1'), false);

        $admin = $this->admin();
        $this->actingAs($admin)->put(route('admin.pages.update', $page), [
            'title' => 'راهنما', 'slug' => 'guide-v2', 'body' => '<p>x</p>', 'is_published' => true,
            'builder_header_override' => '', 'builder_footer_override' => '',
        ])->assertRedirect();

        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee(route('page.show', 'guide-v2'), false);
        $response->assertDontSee(route('page.show', 'guide-v1'), false);

        $this->get(route('page.show', 'guide-v2'))->assertOk()->assertSee('راهنما');
    }

    public function test_nav_source_custom_keeps_the_blocks_own_nav_links_unaffected_by_any_menu(): void
    {
        $page = Page::create(['title' => 'اخبار', 'slug' => 'news', 'body' => '<p>x</p>', 'is_published' => true]);
        $menu = Menu::create(['name' => 'اصلی', 'location' => 'primary', 'site_id' => null]);
        MenuItem::create([
            'menu_id' => $menu->id, 'title' => 'اخبار',
            'type' => MenuItem::TYPE_INTERNAL_PAGE, 'reference_id' => $page->id, 'order' => 0,
        ]);

        $this->installThemeWithNavHeader('custom');

        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee('لینک سفارشی قدیمی');
        // Existing themes that never opted in must never start showing Menu content by default.
        $response->assertDontSee(route('page.show', 'news'), false);
    }

    // ── Footer half of the convergence (BLD-26) ─────────────────────────

    private function installThemeWithNavFooter(string $navSource = 'menu'): void
    {
        Setting::set('active_canvas_theme_data', json_encode([
            'name' => 'Test Theme', 'slug' => 'test-theme',
            'header_blocks' => [],
            'footer_blocks' => [
                ['id' => 'ftr1', 'type' => 'nav_footer', 'props' => [
                    'nav_source' => $navSource,
                    'columns' => [['title' => 'ستون قدیمی', 'links' => [['label' => 'لینک سفارشی قدیمی فوتر', 'url' => '/old-footer-link']]]],
                ]],
            ],
            'pages' => [['key' => 'home', 'title' => 'Home', 'blocks' => [
                ['id' => 'b1', 'type' => 'heading', 'props' => ['text' => 'خانه']],
            ]]],
            'global_styles' => [],
        ], JSON_UNESCAPED_UNICODE), 'theme');
        Setting::set('active_canvas_theme_name', 'Test Theme', 'theme');
    }

    public function test_footer_nav_source_menu_renders_real_footer_menu_items_not_the_blocks_own_columns(): void
    {
        $page = Page::create(['title' => 'حریم خصوصی', 'slug' => 'privacy', 'body' => '<p>x</p>', 'is_published' => true]);
        $menu = Menu::create(['name' => 'فوتر', 'location' => 'footer', 'site_id' => null]);
        MenuItem::create([
            'menu_id' => $menu->id, 'title' => 'حریم خصوصی',
            'type' => MenuItem::TYPE_INTERNAL_PAGE, 'reference_id' => $page->id, 'order' => 0,
        ]);

        $this->installThemeWithNavFooter('menu');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('حریم خصوصی');
        $response->assertSee(route('page.show', 'privacy'), false);
        $response->assertDontSee('لینک سفارشی قدیمی فوتر');
    }

    public function test_footer_nav_source_custom_keeps_the_blocks_own_columns_unaffected_by_any_footer_menu(): void
    {
        $page = Page::create(['title' => 'شرایط استفاده', 'slug' => 'terms', 'body' => '<p>x</p>', 'is_published' => true]);
        $menu = Menu::create(['name' => 'فوتر', 'location' => 'footer', 'site_id' => null]);
        MenuItem::create([
            'menu_id' => $menu->id, 'title' => 'شرایط استفاده',
            'type' => MenuItem::TYPE_INTERNAL_PAGE, 'reference_id' => $page->id, 'order' => 0,
        ]);

        $this->installThemeWithNavFooter('custom');

        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee('لینک سفارشی قدیمی فوتر');
        $response->assertDontSee(route('page.show', 'terms'), false);
    }

    public function test_admin_preview_shows_real_footer_menu_content_not_a_permanently_empty_footer(): void
    {
        $page = Page::create(['title' => 'پشتیبانی', 'slug' => 'support', 'body' => '<p>x</p>', 'is_published' => true]);
        $menu = Menu::create(['name' => 'فوتر', 'location' => 'footer', 'site_id' => null]);
        MenuItem::create([
            'menu_id' => $menu->id, 'title' => 'پشتیبانی',
            'type' => MenuItem::TYPE_INTERNAL_PAGE, 'reference_id' => $page->id, 'order' => 0,
        ]);
        $admin = $this->admin();

        $response = $this->actingAs($admin)->postJson(route('builder.preview'), [
            'blocks' => [['id' => 'ftr1', 'type' => 'nav_footer', 'props' => ['nav_source' => 'menu']]],
        ]);

        $response->assertOk();
        self::assertStringContainsString('پشتیبانی', $response->json('html'));
    }

    // ── Site logo fallback (BLD-25) ──────────────────────────────────

    public function test_nav_header_falls_back_to_the_real_uploaded_site_logo_when_no_block_level_logo_text_is_authored(): void
    {
        $media = \App\Models\CustomerMedia::create([
            'site_id' => 1, 'uuid' => (string) \Illuminate\Support\Str::uuid(), 'disk' => 'public',
            'storage_key' => 'customer-media/1/logo-test.png', 'original_filename' => 'logo.png',
            'extension' => 'png', 'category' => 'image', 'mime_type' => 'image/png', 'size_bytes' => 100,
        ]);
        Setting::set('site_logo', $media->uuid);

        Setting::set('active_canvas_theme_data', json_encode([
            'name' => 'Test Theme', 'slug' => 'test-theme',
            'header_blocks' => [
                ['id' => 'hdr1', 'type' => 'nav_header', 'props' => ['logo_text' => '']],
            ],
            'footer_blocks' => [],
            'pages' => [['key' => 'home', 'title' => 'Home', 'blocks' => [['id' => 'b1', 'type' => 'heading', 'props' => ['text' => 'خانه']]]]],
            'global_styles' => [],
        ], JSON_UNESCAPED_UNICODE), 'theme');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('tb-nav-header-logo', false);
        $response->assertSee('<img', false);
    }

    public function test_nav_header_never_overrides_an_explicitly_authored_text_logo_with_the_site_logo(): void
    {
        $media = \App\Models\CustomerMedia::create([
            'site_id' => 1, 'uuid' => (string) \Illuminate\Support\Str::uuid(), 'disk' => 'public',
            'storage_key' => 'customer-media/1/logo-test2.png', 'original_filename' => 'logo.png',
            'extension' => 'png', 'category' => 'image', 'mime_type' => 'image/png', 'size_bytes' => 100,
        ]);
        Setting::set('site_logo', $media->uuid);

        Setting::set('active_canvas_theme_data', json_encode([
            'name' => 'Test Theme', 'slug' => 'test-theme',
            'header_blocks' => [
                ['id' => 'hdr1', 'type' => 'nav_header', 'props' => ['logo_text' => 'برند من']],
            ],
            'footer_blocks' => [],
            'pages' => [['key' => 'home', 'title' => 'Home', 'blocks' => [['id' => 'b1', 'type' => 'heading', 'props' => ['text' => 'خانه']]]]],
            'global_styles' => [],
        ], JSON_UNESCAPED_UNICODE), 'theme');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('برند من');
    }

    public function test_admin_preview_shows_real_menu_content_not_a_permanently_empty_nav(): void
    {
        $page = Page::create(['title' => 'تماس', 'slug' => 'contact', 'body' => '<p>x</p>', 'is_published' => true]);
        $menu = Menu::create(['name' => 'اصلی', 'location' => 'primary', 'site_id' => null]);
        MenuItem::create([
            'menu_id' => $menu->id, 'title' => 'تماس',
            'type' => MenuItem::TYPE_INTERNAL_PAGE, 'reference_id' => $page->id, 'order' => 0,
        ]);
        $admin = $this->admin();

        $response = $this->actingAs($admin)->postJson(route('builder.preview'), [
            'blocks' => [['id' => 'hdr1', 'type' => 'nav_header', 'props' => ['nav_source' => 'menu', 'logo_text' => 'X']]],
        ]);

        $response->assertOk();
        self::assertStringContainsString('تماس', $response->json('html'));
    }
}

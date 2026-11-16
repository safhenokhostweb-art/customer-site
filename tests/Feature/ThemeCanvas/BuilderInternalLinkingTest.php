<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsValidLicenseWithShop;
use Tests\TestCase;

/**
 * TASK 3/3 Phase 6.5C (BLD-28) — Builder internal linking convergence.
 * button/cta/hero* action URL fields previously only accepted a raw
 * string. They now also accept the canonical
 * {"type":"internal_page","page_id":N} shape (resolved by
 * InternalLinkResolver, mirroring CustomerMediaResolver's own {media_id}
 * pattern) — proven end to end on the real public render, including
 * surviving a Page slug rename (the same guarantee
 * HeaderMenuIntegrationTest already proved for Menu items, reusing the
 * SAME Page::id stable-identity contract).
 */
class BuilderInternalLinkingTest extends TestCase
{
    use RefreshDatabase, SeedsValidLicenseWithShop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markSiteAsInstalled();
    }

    private function installThemeWithButton(mixed $urlValue): void
    {
        Setting::set('active_canvas_theme_data', json_encode([
            'name' => 'Test Theme', 'slug' => 'test-theme',
            'header_blocks' => [], 'footer_blocks' => [],
            'pages' => [['key' => 'home', 'title' => 'Home', 'blocks' => [
                ['id' => 'btn1', 'type' => 'button', 'props' => ['text' => 'مشاهده', 'url' => $urlValue]],
            ]]],
            'global_styles' => [],
        ], JSON_UNESCAPED_UNICODE), 'theme');
        Setting::set('active_canvas_theme_name', 'Test Theme', 'theme');
    }

    public function test_internal_page_target_resolves_to_the_pages_real_current_url(): void
    {
        $page = Page::create(['title' => 'قیمت‌گذاری', 'slug' => 'pricing', 'body' => '<p>x</p>', 'is_published' => true]);
        $this->installThemeWithButton(['type' => 'internal_page', 'page_id' => $page->id]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee(route('page.show', 'pricing'), false);
    }

    public function test_legacy_plain_string_url_is_unaffected(): void
    {
        $this->installThemeWithButton('https://external.example/promo');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('https://external.example/promo', false);
    }

    public function test_internal_page_target_survives_a_slug_rename(): void
    {
        $page = Page::create(['title' => 'راهنما', 'slug' => 'guide-old', 'body' => '<p>x</p>', 'is_published' => true]);
        $this->installThemeWithButton(['type' => 'internal_page', 'page_id' => $page->id]);

        $this->get('/')->assertSee(route('page.show', 'guide-old'), false);

        $admin = User::factory()->create(['is_admin' => true]);
        $this->seedValidLicense([]);
        $this->actingAs($admin)->put(route('admin.pages.update', $page), [
            'title' => 'راهنما', 'slug' => 'guide-new', 'body' => '<p>x</p>', 'is_published' => true,
            'builder_header_override' => '', 'builder_footer_override' => '',
        ])->assertRedirect();

        $response = $this->get('/');
        $response->assertSee(route('page.show', 'guide-new'), false);
        $response->assertDontSee(route('page.show', 'guide-old'), false);
    }

    /**
     * Phase 0.2b (ADR-061): header/footer/promo URL fields became `link`
     * fields, so the editor's page picker is offered on them too. A picked
     * page must render as that page's URL on every one of them — not only
     * on the original button/cta/hero allowlist.
     */
    public function test_every_top_level_link_field_resolves_a_picked_page(): void
    {
        $page = Page::create(['title' => 'تماس', 'slug' => 'contact-us', 'body' => '<p>x</p>', 'is_published' => true]);
        $ref = ['type' => 'internal_page', 'page_id' => $page->id];
        Setting::set('active_canvas_theme_data', json_encode([
            'name' => 'Test Theme', 'slug' => 'test-theme',
            'header_blocks' => [], 'footer_blocks' => [],
            'pages' => [['key' => 'home', 'title' => 'Home', 'blocks' => [
                ['id' => 'nh1', 'type' => 'nav_header', 'props' => ['logo_text' => 'برند', 'logo_url' => $ref, 'cta_text' => 'مشاوره', 'cta_url' => $ref]],
                ['id' => 'pb1', 'type' => 'promo_banner', 'props' => ['text' => 'تخفیف ویژه', 'link' => $ref]],
                ['id' => 'ab1', 'type' => 'about_section', 'props' => ['title' => 'درباره ما', 'btn_text' => 'بیشتر', 'btn_url' => $ref]],
            ]]],
            'global_styles' => [],
        ], JSON_UNESCAPED_UNICODE), 'theme');
        Setting::set('active_canvas_theme_name', 'Test Theme', 'theme');

        $html = $this->get('/')->assertOk()->getContent();

        $href = 'href="' . e(route('page.show', 'contact-us')) . '"';
        // 5: the header CTA also renders its drawer-only copy in the mobile menu.
        $this->assertSame(5, substr_count($html, $href), 'logo, header CTA (bar and drawer), promo and about button all link to the picked page');
        $this->assertStringNotContainsString('render_error', $html);
    }

    public function test_a_forged_or_unpublished_page_id_resolves_to_no_link_not_a_guessed_url(): void
    {
        $this->installThemeWithButton(['type' => 'internal_page', 'page_id' => 999999]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('/page/', false);

        $draft = Page::create(['title' => 'پیش‌نویس', 'slug' => 'draft-page', 'body' => '<p>x</p>', 'is_published' => false]);
        $this->installThemeWithButton(['type' => 'internal_page', 'page_id' => $draft->id]);
        $this->get('/')->assertDontSee(route('page.show', 'draft-page'), false);
    }
}

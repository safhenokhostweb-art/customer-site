<?php

namespace Tests\Unit;

use App\Services\SeoService;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * SEO Gap Report C-3 / C-4 follow-up — per-page SEO for Canvas pages.
 *
 * A Canvas page has no Eloquent model: its title, block tree and optional
 * SEO overrides live in one array inside the theme JSON. Before this change
 * the public layout rendered `<x-seo-meta :title="..." />` and nothing else,
 * so every Canvas page on a tenant with no `seo_default_description`
 * shipped `<meta name="description" content="">` — a site-wide empty
 * description on every page.
 *
 * SeoService::canvasPage() is now the single resolver for those values, and
 * these tests pin the guarantee that actually matters: a Canvas page NEVER
 * renders an empty description, and never renders the same generic
 * description as every other page on the site when its own content can
 * produce a real one.
 */
class CanvasPageSeoTest extends TestCase
{
    private function canvasPage(?array $page = null, array $blocks = [], ?string $fallbackTitle = null): array
    {
        return app(SeoService::class)->canvasPage($page, $blocks, $fallbackTitle);
    }

    // ── description: page's own content first, never empty ────────────────

    public function test_description_is_derived_from_the_pages_own_blocks(): void
    {
        $meta = $this->canvasPage(
            ['title' => 'دربارهٔ ما'],
            [
                ['type' => 'section', 'props' => [], 'children' => [
                    ['type' => 'text', 'props' => ['content' => '<p>ما از سال ۱۳۹۰ در خدمت مشتریان هستیم.</p>']],
                ]],
            ],
        );

        $this->assertSame('ما از سال ۱۳۹۰ در خدمت مشتریان هستیم.', $meta['description']);
    }

    public function test_explicit_page_seo_description_wins_over_derived_block_text(): void
    {
        $meta = $this->canvasPage(
            ['title' => 'تماس', 'seo' => ['description' => 'راه‌های ارتباط با تیم فروش.']],
            [['type' => 'text', 'props' => ['content' => 'متنی که نباید استفاده شود']]],
        );

        $this->assertSame('راه‌های ارتباط با تیم فروش.', $meta['description']);
    }

    public function test_two_different_pages_never_share_one_generic_description(): void
    {
        $a = $this->canvasPage(['title' => 'خدمات'], [
            ['type' => 'text', 'props' => ['content' => 'طراحی و پیاده‌سازی فروشگاه اینترنتی.']],
        ]);
        $b = $this->canvasPage(['title' => 'تماس با ما'], [
            ['type' => 'text', 'props' => ['content' => 'نشانی دفتر مرکزی در تهران.']],
        ]);

        $this->assertNotSame($a['description'], $b['description']);
    }

    public function test_description_is_never_empty_even_with_no_page_and_no_blocks(): void
    {
        $meta = $this->canvasPage([], []);

        $this->assertNotSame('', trim($meta['description']));
    }

    public function test_description_is_never_empty_when_blocks_carry_no_text(): void
    {
        $meta = $this->canvasPage(
            ['title' => 'خانه'],
            [['type' => 'spacer', 'props' => ['height' => 48]], ['type' => 'icon_box', 'props' => []]],
        );

        $this->assertNotSame('', trim($meta['description']));
    }

    public function test_derived_description_is_plain_text_and_length_limited(): void
    {
        $meta = $this->canvasPage(
            ['title' => 'وبلاگ'],
            [['type' => 'text', 'props' => ['content' => '<p>' . str_repeat('کلمه ', 200) . '</p>']]],
        );

        $this->assertStringNotContainsString('<p>', $meta['description']);
        $this->assertLessThanOrEqual(163, mb_strlen($meta['description']));
    }

    public function test_malformed_block_entries_do_not_break_resolution(): void
    {
        $meta = $this->canvasPage(
            ['title' => 'صفحه'],
            ['bare-string', ['type' => 'text', 'props' => 'not-an-array'], ['type' => 'text', 'props' => ['content' => 'متن سالم.']]],
        );

        $this->assertSame('متن سالم.', $meta['description']);
    }

    // ── title / canonical / robots ───────────────────────────────────────

    public function test_title_falls_back_through_fallback_title_to_site_identity(): void
    {
        $this->assertSame('عنوان', $this->canvasPage(['title' => 'عنوان'])['title']);
        $this->assertSame('fallback', $this->canvasPage([], [], 'fallback')['title']);
        $this->assertNotSame('', trim($this->canvasPage([], [])['title']));
    }

    public function test_canonical_defaults_to_the_current_url_and_honours_a_page_override(): void
    {
        $this->assertSame(url()->current(), $this->canvasPage(['title' => 'x'])['canonical']);
        $this->assertSame(
            'https://example.com/canonical',
            $this->canvasPage(['title' => 'x', 'canonical' => 'https://example.com/canonical'])['canonical'],
        );
    }

    public function test_robots_defaults_to_the_seo_service_directive_and_honours_page_overrides(): void
    {
        $this->assertSame(app(SeoService::class)->robots(), $this->canvasPage(['title' => 'x'])['robots']);
        $this->assertSame(
            'noindex, follow',
            $this->canvasPage(['title' => 'x', 'seo' => ['robots' => 'noindex, follow']])['robots'],
        );
        $this->assertSame(
            // A page-level noindex keeps link equity flowing: noindex, follow.
            'noindex, follow',
            $this->canvasPage(['title' => 'x', 'noindex' => true])['robots'],
        );
    }

    // ── og image ─────────────────────────────────────────────────────────

    public function test_og_image_is_never_guessed_from_a_block_image_reference(): void
    {
        // A block's `background_image` is a manifest-relative reference
        // (images/hero.jpg) that only becomes a real URL later, in
        // ThemeRuntimeDocument's declared-asset rewrite — guessing it here
        // could emit a 404 OG image. Only an explicit page image is used.
        $meta = $this->canvasPage(
            ['title' => 'x'],
            [['type' => 'hero', 'props' => ['background_image' => 'images/hero.jpg']]],
        );

        $this->assertSame(app(SeoService::class)->imageUrl(), $meta['image']);
    }

    public function test_explicit_page_og_image_is_used_and_alt_defaults_to_the_title(): void
    {
        $meta = $this->canvasPage([
            'title' => 'فروشگاه',
            'og_image' => 'https://example.com/og.png',
        ]);

        $this->assertSame('https://example.com/og.png', $meta['image']);
        $this->assertSame('فروشگاه', $meta['imageAlt']);
    }

    // ── component: the new inputs it must accept ─────────────────────────

    public function test_component_accepts_locale_and_emits_it_as_og_locale(): void
    {
        $html = Blade::render('<x-seo-meta :title="\'T\'" locale="en_US" />');

        $this->assertStringContainsString('<meta property="og:locale" content="en_US">', $html);
    }

    public function test_component_defaults_og_locale_to_fa_ir(): void
    {
        $html = Blade::render('<x-seo-meta :title="\'T\'" />');

        $this->assertStringContainsString('<meta property="og:locale" content="fa_IR">', $html);
    }

    public function test_component_emits_og_image_alt_when_an_image_is_present(): void
    {
        $html = Blade::render('<x-seo-meta :title="\'T\'" image="https://example.com/og.png" imageAlt="تصویر" />');

        $this->assertStringContainsString('<meta property="og:image:alt" content="تصویر">', $html);
    }

    public function test_component_emits_supplied_extra_json_ld(): void
    {
        $html = Blade::render(
            '<x-seo-meta :title="\'T\'" :include-organization-json-ld="false" '
            . ':schema="[[\'@context\' => \'https://schema.org\', \'@type\' => \'BreadcrumbList\']]" />'
        );

        $this->assertStringContainsString('application/ld+json', $html);
        $this->assertStringContainsString('BreadcrumbList', $html);
    }

    public function test_component_never_renders_an_empty_description(): void
    {
        $html = Blade::render('<x-seo-meta :title="\'T\'" description="" />');

        $this->assertStringNotContainsString('<meta name="description" content="">', $html);
    }

    // ── wiring: the public Canvas layout must pass the resolved values ────

    public function test_canvas_layout_passes_the_resolved_seo_values_to_the_component(): void
    {
        $contents = file_get_contents(resource_path('views/layouts/canvas-theme.blade.php'));

        foreach ([':description=', ':image=', ':imageAlt=', ':canonical=', ':robots=', ':ogType=', ':locale='] as $attribute) {
            $this->assertStringContainsString($attribute, $contents);
        }
    }

    public function test_canvas_controller_resolves_seo_meta_for_every_rendered_page(): void
    {
        $contents = file_get_contents(app_path('Http/Controllers/Front/ThemeCanvasPageController.php'));

        $this->assertStringContainsString('canvasPage(', $contents);
        $this->assertStringContainsString("'seoMeta'", $contents);
    }

    /**
     * Regression guard for a real defect this change introduced and the
     * feature tests caught: a Blade {{-- --}} comment placed INSIDE an
     * `@php` block. `artisan view:cache` compiles such a file happily —
     * the syntax error only surfaces when the layout is actually rendered —
     * so "it compiles" is not evidence. TOKEN_PARSE re-parses without
     * executing, which is exactly the check needed.
     */
    public function test_the_canvas_layout_compiles_to_syntactically_valid_php(): void
    {
        $source = file_get_contents(resource_path('views/layouts/canvas-theme.blade.php'));
        $compiled = Blade::compileString($source);

        try {
            token_get_all($compiled, TOKEN_PARSE);
        } catch (\ParseError $e) {
            $this->fail('canvas-theme.blade.php compiles to invalid PHP: ' . $e->getMessage());
        }

        $this->assertTrue(true);
    }
}

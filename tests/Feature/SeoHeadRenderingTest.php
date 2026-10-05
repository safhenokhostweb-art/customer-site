<?php

namespace Tests\Feature;

use App\Services\SeoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * customer-site does not use SaaSPlatform\CoreUtils\SEO\MetaEngine (its vendored
 * core-utils copy predates that package), but it owns the same contract: one
 * component, `<x-seo-meta>`, writes the head tags, and SeoService decides what
 * they say. These tests pin the rendered-HTML shape of every layout that renders
 * a `<head>`, so a second hand-written title/description/canonical/robots cannot
 * be introduced alongside the component, and so a non-public surface cannot ship
 * without a noindex directive.
 *
 * The layout owns `<title>` (that is customer-site's convention) and the
 * component owns description/canonical/robots. Two of the assertions below are
 * regressions for defects found by rendering the real documents:
 *
 *  - layouts/auth-login.blade.php (login + register) emitted no description and
 *    no canonical at all: verified 0/1/1/0 for title/description/canonical/robots.
 *  - admin/layout.blade.php and account/layout.blade.php emitted no
 *    <meta name="robots"> at all — 151 and 23 child views respectively were
 *    reachable with no noindex directive.
 *  - admin/theme-design/canvas-live-preview.blade.php pushed a <title> into
 *    @stack('head'), which layouts/canvas-theme.blade.php renders inside its own
 *    <head> that already has a <title>: verified 2 titles before the fix.
 */
class SeoHeadRenderingTest extends TestCase
{
    use RefreshDatabase;

    private function countSeoTags(string $html): array
    {
        return [
            'title' => preg_match_all('/<title\b/i', $html),
            'description' => preg_match_all('/<meta\s+name="description"/i', $html),
            'canonical' => preg_match_all('/<link\s+rel="canonical"/i', $html),
            'robots' => preg_match_all('/<meta\s+name="robots"/i', $html),
        ];
    }

    /** @param array{title:int,description:int,canonical:int,robots:int} $expected */
    private function assertTagCounts(string $html, array $expected, string $surface): void
    {
        $counts = $this->countSeoTags($html);

        foreach (['title', 'description', 'canonical', 'robots'] as $tag) {
            $this->assertSame(
                $expected[$tag],
                $counts[$tag],
                $surface . ' must render ' . $expected[$tag] . ' ' . $tag . ' tag(s), got ' . $counts[$tag]
            );
        }
    }

    private function stripBladeComments(string $source): string
    {
        return (string) preg_replace('/\{\{--.*?--\}\}/s', '', $source);
    }

    // ── the component itself ─────────────────────────────────────────────

    public function test_the_shared_component_emits_each_of_its_tags_exactly_once(): void
    {
        $html = View::make('components.seo-meta', [
            'title' => 'عنوان',
            'description' => 'توضیح',
            'image' => null,
            'canonical' => 'https://tenant.invalid/x',
            'robots' => 'index, follow',
        ])->render();

        $this->assertTagCounts($html, ['title' => 0, 'description' => 1, 'canonical' => 1, 'robots' => 1], 'components.seo-meta');
        $this->assertStringContainsString('name="robots" content="index, follow"', $html);

        // The layout owns <title>; the component deliberately does not emit one,
        // so a page can never end up with two.
    }

    public function test_the_shared_component_emits_head_tags_only(): void
    {
        $html = View::make('components.seo-meta', ['title' => 'عنوان'])->render();

        $this->assertStringNotContainsString('<!doctype', strtolower($html));
        $this->assertStringNotContainsString('<html', strtolower($html));
        $this->assertStringNotContainsString('<head', strtolower($html));
        $this->assertStringNotContainsString('<body', strtolower($html));
    }

    // ── public layouts: indexable, one of each ────────────────────────────

    public function test_the_public_canvas_layout_writes_no_head_tag_by_hand(): void
    {
        $markup = $this->stripBladeComments(file_get_contents(resource_path('views/layouts/canvas-theme.blade.php')));

        $this->assertStringContainsString('<x-seo-meta', $markup);
        $this->assertSame(1, $this->countSeoTags($markup)['title']);
        $this->assertSame(0, $this->countSeoTags($markup)['description']);
        $this->assertSame(0, $this->countSeoTags($markup)['canonical']);
        $this->assertSame(0, $this->countSeoTags($markup)['robots']);
    }

    public function test_the_default_theme_layout_writes_no_head_tag_by_hand(): void
    {
        $markup = $this->stripBladeComments(file_get_contents(resource_path('views/themes/default/layout.blade.php')));

        $this->assertStringContainsString('<x-seo-meta', $markup);
        $this->assertSame(1, $this->countSeoTags($markup)['title']);
        $this->assertSame(0, $this->countSeoTags($markup)['description']);
        $this->assertSame(0, $this->countSeoTags($markup)['canonical']);
        $this->assertSame(0, $this->countSeoTags($markup)['robots']);
    }

    public function test_the_public_home_page_renders_one_of_each_tag_and_stays_indexable(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $this->assertTagCounts(
            (string) $response->getContent(),
            ['title' => 1, 'description' => 1, 'canonical' => 1, 'robots' => 1],
            'public home'
        );
        $response->assertSee('<meta name="robots" content="index, follow">', false);
    }

    // ── non-public layouts: noindex, no description of its own, no duplicates ──

    public function test_every_non_public_layout_delegates_to_the_single_renderer(): void
    {
        $layouts = [
            'layouts/auth-login.blade.php' => 'auth (login + register)',
            'admin/layout.blade.php' => 'admin (151 child views)',
            'account/layout.blade.php' => 'account (23 child views)',
            'layouts/canvas-editor.blade.php' => 'canvas editor',
        ];

        foreach ($layouts as $path => $label) {
            $markup = $this->stripBladeComments(file_get_contents(resource_path('views/' . $path)));

            $this->assertStringContainsString(
                '<x-seo-meta',
                $markup,
                $label . ' must delegate its head tags to <x-seo-meta>'
            );
            $this->assertSame(1, $this->countSeoTags($markup)['title'], $label . ' must own exactly one <title>');
            $this->assertSame(0, $this->countSeoTags($markup)['description'], $label . ' must not hand-write a description');
            $this->assertSame(0, $this->countSeoTags($markup)['canonical'], $label . ' must not hand-write a canonical');
            $this->assertSame(0, $this->countSeoTags($markup)['robots'], $label . ' must not hand-write a robots tag');
        }
    }

    public function test_the_login_and_register_pages_render_one_of_each_tag_and_are_not_indexable(): void
    {
        foreach (['/login', '/register'] as $uri) {
            $response = $this->get($uri);

            $response->assertOk();
            $this->assertTagCounts(
                (string) $response->getContent(),
                ['title' => 1, 'description' => 1, 'canonical' => 1, 'robots' => 1],
                'auth surface ' . $uri
            );
            $this->assertStringContainsString(
                '<meta name="robots" content="noindex, nofollow">',
                (string) $response->getContent(),
                $uri . ' must be noindex, nofollow'
            );
        }
    }

    // ── preview: the live-preview iframe ──────────────────────────────────

    public function test_the_live_preview_document_renders_exactly_one_title(): void
    {
        $viewData = app(\App\Services\ThemeCanvas\ThemeRuntimeDocument::class)->build(
            ['pages' => ['home' => ['title' => 'خانهٔ تست', 'blocks' => []]]],
            [],
            'خانهٔ تست',
            'home',
            editorPreview: true,
            editorRegion: null,
        ) + ['themeName' => ''];

        $html = (string) view('admin.theme-design.canvas-live-preview', $viewData)->render();

        $this->assertTagCounts(
            $html,
            ['title' => 1, 'description' => 1, 'canonical' => 1, 'robots' => 1],
            'canvas live preview'
        );
        $this->assertStringContainsString(
            '<meta name="robots" content="noindex, nofollow">',
            $html,
            'a preview document must never be indexable'
        );
    }

    public function test_the_live_preview_partial_pushes_no_title_into_the_layout_head_stack(): void
    {
        $markup = $this->stripBladeComments(
            file_get_contents(resource_path('views/admin/theme-design/canvas-live-preview.blade.php'))
        );

        $this->assertSame(0, $this->countSeoTags($markup)['title']);
    }

    // ── resolution still works ────────────────────────────────────────────

    public function test_a_canvas_page_seo_override_reaches_the_resolved_meta(): void
    {
        $meta = app(SeoService::class)->canvasPage([
            'title' => 'صفحهٔ تماس',
            'seo' => ['description' => 'توضیح اختصاصی صفحهٔ تماس.', 'robots' => 'noindex, follow'],
        ]);

        $this->assertSame('توضیح اختصاصی صفحهٔ تماس.', $meta['description']);
        $this->assertSame('noindex, follow', $meta['robots']);
        $this->assertSame('صفحهٔ تماس', $meta['title']);
    }

    public function test_a_canvas_page_without_any_seo_data_still_resolves_a_robots_directive(): void
    {
        $meta = app(SeoService::class)->canvasPage(['title' => 'بدون توضیح'], []);

        $this->assertNotSame('', trim($meta['description']));
        $this->assertNotSame('', trim($meta['robots']));
    }
}

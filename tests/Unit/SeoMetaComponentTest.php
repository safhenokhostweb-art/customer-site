<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Blade;
use SaaSPlatform\CoreUtils\Contracts\Services\SeoOverrideProviderInterface;
use Tests\TestCase;

/**
 * SEO Parity — <x-seo-meta> is the shared component both the legacy theme
 * layout and the Template Builder's canvas-theme layout now render through,
 * so a page built with the Template Builder gets the exact same OG/Twitter/
 * JSON-LD coverage a legacy-theme page always had.
 *
 * These tests exercise the component in isolation (it wraps App\Helpers\Seo
 * and App\Helpers\JsonLd — no SEO logic lives in the component itself), plus
 * a plain file-content check that both real layouts actually render through it.
 */
class SeoMetaComponentTest extends TestCase
{
    private function render(string $props): string
    {
        return Blade::render('<x-seo-meta ' . $props . ' />');
    }

    public function test_default_render_includes_title_description_canonical_and_robots(): void
    {
        $html = $this->render(':title="\'My Title\'"');

        $this->assertStringContainsString('<meta property="og:title" content="My Title">', $html);
        $this->assertStringContainsString('<meta name="description"', $html);
        $this->assertStringContainsString('<meta name="robots"', $html);
        $this->assertStringContainsString('<link rel="canonical"', $html);
    }

    public function test_explicit_props_override_seo_service_defaults(): void
    {
        $html = $this->render(implode(' ', [
            ':title="\'Explicit Title\'"',
            'description="Explicit description"',
            'canonical="https://example.com/page"',
            'robots="noindex, nofollow"',
            'og-type="article"',
        ]));

        $this->assertStringContainsString('og:description" content="Explicit description"', $html);
        $this->assertStringContainsString('<link rel="canonical" href="https://example.com/page">', $html);
        $this->assertStringContainsString('<meta name="robots" content="noindex, nofollow">', $html);
        $this->assertStringContainsString('<meta property="og:type" content="article">', $html);
        $this->assertStringContainsString('twitter:title" content="Explicit Title"', $html);
        $this->assertStringContainsString('twitter:description" content="Explicit description"', $html);
    }

    public function test_og_and_twitter_image_tags_render_when_image_is_present(): void
    {
        $html = $this->render(':title="\'T\'" image="https://example.com/og.png"');

        $this->assertStringContainsString('<meta property="og:image" content="https://example.com/og.png">', $html);
        $this->assertStringContainsString('<meta name="twitter:image" content="https://example.com/og.png">', $html);
    }

    public function test_image_tags_are_omitted_when_no_image_is_available(): void
    {
        $html = $this->render(':title="\'T\'"');

        $this->assertStringNotContainsString('og:image', $html);
        $this->assertStringNotContainsString('twitter:image', $html);
    }

    public function test_json_ld_organization_and_website_render_by_default(): void
    {
        $html = $this->render(':title="\'T\'"');

        $this->assertStringContainsString('application/ld+json', $html);
        $this->assertStringContainsString('"@type":"Organization"', $html);
        $this->assertStringContainsString('"@type":"WebSite"', $html);
    }

    public function test_json_ld_can_be_suppressed_per_page(): void
    {
        $html = $this->render(':title="\'T\'" :include-organization-json-ld="false"');

        $this->assertStringNotContainsString('application/ld+json', $html);
    }

    public function test_component_respects_a_bound_seo_override_provider(): void
    {
        $this->app->bind(SeoOverrideProviderInterface::class, function () {
            return new class implements SeoOverrideProviderInterface {
                public function overrideFor(): ?array
                {
                    return ['description' => 'توضیح از ماژول SEO'];
                }
                public function extraJsonLd(): array { return []; }
            };
        });

        $html = $this->render(':title="\'T\'"');

        $this->assertStringContainsString('توضیح از ماژول SEO', $html);
    }

    // ── wiring: both public layouts must render through the shared component ──

    public function test_legacy_theme_layout_renders_seo_through_the_shared_component(): void
    {
        $contents = file_get_contents(resource_path('views/themes/default/layout.blade.php'));

        $this->assertStringContainsString('<x-seo-meta', $contents);
    }

    public function test_canvas_theme_layout_renders_seo_through_the_shared_component(): void
    {
        $contents = file_get_contents(resource_path('views/layouts/canvas-theme.blade.php'));

        $this->assertStringContainsString('<x-seo-meta', $contents);
    }
}

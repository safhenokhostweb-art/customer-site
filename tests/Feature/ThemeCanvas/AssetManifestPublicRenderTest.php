<?php

namespace Tests\Feature\ThemeCanvas;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Phase 8 Step 1 — the manifest on the real public render path.
 *
 * The layout must embed the deduplicated asset manifest as a JSON island
 * (data-tb-asset-manifest) exactly when the controller produced one, with
 * the manifest's CSS eagerly present (preload + stylesheet) and its JS
 * hydrated by the non-blocking loader — and it must fall back to the
 * historical eager global injection when the manifest is null.
 */
class AssetManifestPublicRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        config([
            'site.owner_site' => true,
            'services.customer_provision_secret' => 'phase8-test-secret',
        ]);
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    private function installPayload(array $overrides = []): array
    {
        return array_merge([
            'theme_id' => 'phase8-asset-manifest-test',
            'theme_name' => 'Phase 8 Asset Manifest Test',
            'source_theme_id' => '998',
            'source_theme_version' => '1.0.0',
            'source_release_id' => 998,
            'header_blocks' => [
                ['type' => 'nav_header', 'props' => ['logo_text' => 'Test Brand', 'nav_links' => []]],
            ],
            'footer_blocks' => [
                ['type' => 'nav_footer', 'props' => ['logo_text' => 'Test Brand', 'copyright' => '©']],
            ],
            'pages' => [
                'home' => ['title' => 'Home', 'slug' => '', 'is_home' => true, 'blocks' => [
                    ['type' => 'slider', 'id' => 's1', 'props' => ['slides' => []]],
                    ['type' => 'contact_split', 'id' => 'c1', 'props' => []],
                    ['type' => 'text', 'id' => 't1', 'props' => ['content' => 'متن']],
                ]],
            ],
            'global_styles' => ['primary_color' => '#4f46e5'],
            'extension_assets' => [
                'slider' => ['css' => ['https://cdn.extension.test/shared.css'], 'js' => ['https://cdn.extension.test/slider.js']],
                'contact_split' => ['css' => ['https://cdn.extension.test/shared.css'], 'js' => ['https://cdn.extension.test/contact.js']],
            ],
        ], $overrides);
    }

    private function install(array $overrides = []): TestResponse
    {
        return $this->withHeaders(['X-Provision-Secret' => 'phase8-test-secret'])
            ->postJson(route('builder.api.theme.install'), $this->installPayload($overrides));
    }

    public function test_public_render_embeds_the_manifest_and_injects_only_manifest_assets(): void
    {
        $this->install()->assertOk()->assertJson(['success' => true]);

        $home = $this->get('/');
        $home->assertOk();

        $html = $home->getContent();

        // The JSON island exists, with the deduplicated assets of the page's
        // three blocks (shared.css once — two blocks declared it).
        $this->assertSame(1, preg_match('/    <script type="application\/json"[^>]*data-tb-asset-manifest>(.*?)<\/script>/s', $html, $m));
        $manifest = json_decode($m[1], true);
        $this->assertSame(['https://cdn.extension.test/shared.css'], $manifest['css']);
        $this->assertSame(['https://cdn.extension.test/slider.js', 'https://cdn.extension.test/contact.js'], $manifest['js']);

        // Manifest CSS is eagerly present exactly once, preload + stylesheet.
        $this->assertSame(1, substr_count($html, '<link rel="preload" as="style" href="https://cdn.extension.test/shared.css">'));
        $this->assertSame(1, substr_count($html, '<link rel="stylesheet" href="https://cdn.extension.test/shared.css">'));

        // The non-blocking hydration bridge is present.
        $this->assertStringContainsString('data-tb-asset-manifest', $html);
        $this->assertStringContainsString('s.async = true', $html);
    }

    public function test_manifest_failure_falls_back_to_safe_default_eager_injection(): void
    {
        // Theme-global assets (real, localized during install) are the assets
        // the Safe Default must keep serving eagerly when the manifest fails.
        $css = 'body{color:#888}';
        $js = 'window.themeReady=true;';
        config(['site.platform_url' => 'https://platform.test']);
        \Illuminate\Support\Facades\Http::fake([
            'https://platform.test/release/theme.css' => \Illuminate\Support\Facades\Http::response($css, 200, ['Content-Type' => 'text/css']),
            'https://platform.test/release/theme.js' => \Illuminate\Support\Facades\Http::response($js, 200, ['Content-Type' => 'application/javascript']),
        ]);

        $this->install(['theme_assets' => ['version' => 1, 'assets' => [
            ['kind' => 'css', 'source' => 'https://platform.test/release/theme.css', 'path' => 'css/theme.css', 'sha256' => hash('sha256', $css)],
            ['kind' => 'js', 'source' => 'https://platform.test/release/theme.js', 'path' => 'js/theme.js', 'sha256' => hash('sha256', $js)],
        ]]])->assertOk()->assertJson(['success' => true]);

        // Force generation to fail at runtime: bind the service to a layout
        // service double whose header resolution throws.
        $this->app->bind(\App\Services\ThemeCanvas\AssetManifestGeneratorService::class, function () {
            $layout = \Mockery::mock(\App\Services\BuilderLayoutService::class);
            $layout->shouldReceive('getHeaderBlocks')->andThrow(new \RuntimeException('boom'));
            $layout->shouldReceive('getFooterBlocks')->andReturn([]);

            return new \App\Services\ThemeCanvas\AssetManifestGeneratorService(
                $layout,
                app(\App\Services\ThemeCanvas\ThemeAssetLocalizationService::class),
            );
        });

        $html = $this->get('/')->getContent();

        // Safe Default: the historical eager global injection — theme-global
        // CSS and JS present, no manifest island.
        $this->assertStringNotContainsString('data-tb-asset-manifest', $html);
        $this->assertMatchesRegularExpression('/<link rel="stylesheet" href="[^"]*\/storage\/theme-assets\/[^"]*css\/theme\.css">/', $html);
        $this->assertMatchesRegularExpression('/<script src="[^"]*\/storage\/theme-assets\/[^"]*js\/theme\.js" defer><\/script>/', $html);
    }
}

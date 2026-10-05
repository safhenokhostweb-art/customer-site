<?php

namespace Tests\Unit;

use App\Services\BuilderLayoutService;
use App\Services\ThemeCanvas\AssetManifestGeneratorService;
use App\Services\ThemeCanvas\ThemeAssetLocalizationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

/**
 * Phase 8 Step 1 — Asset-on-Demand manifest generation.
 *
 * The manifest must contain EXACTLY the assets required by the blocks
 * actually present on the page (no others), deduplicated by URL, with the
 * same type vocabulary the renderer resolves (zero drift: an asset entry
 * for a type the registries do not know must never reach the page), with
 * platform-origin asset URLs resolved to their verified customer-local
 * copies, and with any generation failure producing null (the layout's
 * Safe Default fallback), never an exception.
 */
class AssetManifestGeneratorServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** One localized (already-stored) theme stylesheet + two Extension CDN URLs. */
    private function themeData(): array
    {
        config(['site.platform_url' => 'https://master-theme.test']);

        Storage::fake('public');
        $cssBody = 'body{color:#333}';
        $storageKey = 'theme-assets/qa/rel1/'.str_repeat('a', 64).'/styles/main.css';
        Storage::disk('public')->put($storageKey, $cssBody);

        $platformCss = 'https://master-theme.test/storage/theme-assets/qa/rel1/'.str_repeat('a', 64).'/styles/main.css';

        return [
            'extension_assets' => [
                // Slider + ContactForm both require the same stylesheet →
                // exactly ONE entry after dedup; each also has its own JS.
                'slider' => ['css' => [$platformCss], 'js' => ['https://cdn.extension.test/slider.js']],
                'contact_split' => ['css' => [$platformCss], 'js' => ['https://cdn.extension.test/contact.js']],
                // Text requires nothing — absent from the map entirely.
                // Drift guard: an entry for a type NO registry knows must
                // never load, however plausible the URL looks.
                'ghost_block_not_in_any_registry' => ['js' => ['https://cdn.extension.test/evil.js']],
            ],
            'theme_assets' => [
                'version' => 1,
                'localized' => true,
                'manifest_sha256' => str_repeat('a', 64),
                'assets' => [
                    ['kind' => 'css', 'path' => 'styles/main.css', 'sha256' => hash('sha256', $cssBody), 'storage_key' => $storageKey],
                ],
            ],
        ];
    }

    /** Header + the three verification-plan content blocks (Slider, ContactForm, Text). */
    private function generator(array $themeData, ?array &$capturedContent = null): AssetManifestGeneratorService
    {
        $layout = Mockery::mock(BuilderLayoutService::class);
        $layout->shouldReceive('getHeaderBlocks')->andReturn([['type' => 'nav_header', 'props' => ['logo_text' => 'T']]]);
        $layout->shouldReceive('getFooterBlocks')->andReturn([['type' => 'nav_footer', 'props' => ['logo_text' => 'T', 'copyright' => '©']]]);

        return new AssetManifestGeneratorService(
            $layout,
            app(ThemeAssetLocalizationService::class),
        );
    }

    public function test_manifest_contains_exactly_the_three_blocks_assets_deduplicated_and_localized(): void
    {
        Http::fake(); // the localized copy must resolve from storage, never over the wire

        $themeData = $this->themeData();
        $service = $this->generator($themeData);

        $manifest = $service->forCanvasPage($themeData, [
            ['type' => 'slider', 'id' => 's1', 'props' => []],
            ['type' => 'contact_split', 'id' => 'c1', 'props' => []],
            ['type' => 'text', 'id' => 't1', 'props' => ['content' => 'سلام']],
        ]);

        $this->assertNotNull($manifest);
        // The platform-origin stylesheet resolved to its verified local URL,
        // once (Slider and ContactForm both declared it).
        $this->assertCount(1, $manifest['css']);
        $this->assertStringStartsWith('/storage/theme-assets/', $manifest['css'][0]);
        // The Extension CDN scripts, in declared order, nothing else.
        $this->assertSame(
            ['https://cdn.extension.test/slider.js', 'https://cdn.extension.test/contact.js'],
            $manifest['js'],
        );
        // No blocking over the wire for the localized asset, no evil.js
        // from the unknown type, nothing for the asset-less text block.
        Http::assertNothingSent();
    }

    public function test_unknown_block_type_is_never_loaded_zero_drift(): void
    {
        $themeData = $this->themeData();
        $service = $this->generator($themeData);

        $manifest = $service->forCanvasPage($themeData, [
            ['type' => 'ghost_block_not_in_any_registry', 'id' => 'g1', 'props' => []],
        ]);

        $this->assertNotNull($manifest);
        $this->assertSame(['css' => [], 'js' => []], $manifest);
    }

    public function test_generation_failure_returns_null_safe_default_fallback(): void
    {
        $layout = Mockery::mock(BuilderLayoutService::class);
        $layout->shouldReceive('getHeaderBlocks')->andThrow(new \RuntimeException('variant resolution exploded'));
        $service = new AssetManifestGeneratorService($layout, app(ThemeAssetLocalizationService::class));

        $this->assertNull($service->forCanvasPage($this->themeData(), [
            ['type' => 'slider', 'id' => 's1', 'props' => []],
        ]));
    }
}

<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\License;
use App\Services\ThemeCanvas\ThemeCanvasRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Theme Builder <-> Motion Graphics integration — module:motion.
 *
 * ThemeCanvasRenderer::renderModuleMotion() contains zero Motion
 * resolution/rendering logic of its own; it only calls the existing,
 * previously-dormant <x-motion.graphics> component
 * (resources/views/components/motion/graphics.blade.php), which in turn
 * calls the existing MotionRuntimeManifestReader / AssetResolver / Renderer
 * chain unmodified. These tests fake the platform HTTP boundary
 * (GET {platform}/api/motion-runtime/manifest) — the same boundary
 * Wave4MarketplaceAndBannerTest already fakes for marketplace calls — and
 * assert the whole chain behaves safely end to end.
 */
class MotionModuleBlockTest extends TestCase
{
    use RefreshDatabase;

    protected ThemeCanvasRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new ThemeCanvasRenderer();
        Cache::forget('motion.runtime.manifest');
        config(['site.platform_url' => 'https://platform.example']);
        License::create([
            'license_key' => 'MOTION-TEST-KEY',
            'domain' => null,
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => [],
        ]);
    }

    private function fakeManifest(array $assets): void
    {
        Http::fake([
            'platform.example/api/motion-runtime/manifest*' => Http::response([
                'available' => true,
                'schema_version' => '1.0',
                'site_reference' => 'site-1',
                'manifest_checksum' => str_repeat('a', 64),
                'assets' => $assets,
            ], 200),
        ]);
    }

    private function renderBlock(array $props): string
    {
        return $this->renderer->renderBlock([
            'type' => 'module:motion',
            'id' => 'm1',
            'props' => $props,
        ]);
    }

    // ── #10 renders through the existing runtime when the asset resolves ──

    public function test_motion_block_renders_through_the_existing_runtime_component(): void
    {
        $this->fakeManifest([[
            'asset_id' => 'medical-hero-intro',
            'published_version' => 1,
            'checksum' => str_repeat('b', 64),
            'asset_type' => 'svg',
            'optimized_reference' => '/motion/medical-hero-intro/v1/a.svg',
        ]]);

        $html = $this->renderBlock(['motion_asset_id' => 'medical-hero-intro']);

        $this->assertStringContainsString('<img', $html);
        $this->assertStringContainsString('medical-hero-intro/v1/a.svg', $html);
        $this->assertStringContainsString('tb-module-motion', $html);
    }

    // ── #6 missing reference does not break the page ───────────────────────

    public function test_empty_motion_reference_renders_nothing_and_does_not_throw(): void
    {
        $this->fakeManifest([]);

        $html = $this->renderBlock(['motion_asset_id' => '']);

        $this->assertSame('', $html);
    }

    public function test_unresolvable_asset_id_renders_nothing_safely(): void
    {
        $this->fakeManifest([[
            'asset_id' => 'other-asset',
            'published_version' => 1,
            'checksum' => str_repeat('c', 64),
            'asset_type' => 'svg',
            'optimized_reference' => '/motion/other-asset/v1/a.svg',
        ]]);

        $html = $this->renderBlock(['motion_asset_id' => 'medical-hero-intro']);

        $this->assertSame('', $html);
    }

    // ── customer control: enable/disable (Step 6) ──────────────────────────

    public function test_customer_can_disable_an_assigned_motion_block(): void
    {
        $this->fakeManifest([[
            'asset_id' => 'medical-hero-intro',
            'published_version' => 1,
            'checksum' => str_repeat('b', 64),
            'asset_type' => 'svg',
            'optimized_reference' => '/motion/medical-hero-intro/v1/a.svg',
        ]]);

        $html = $this->renderBlock(['motion_asset_id' => 'medical-hero-intro', 'enabled' => false]);

        $this->assertSame('', $html);
    }

    // ── graceful fallback (Step 7 / Step 2 "display settings") ─────────────

    public function test_fallback_image_renders_when_motion_is_unavailable(): void
    {
        $this->fakeManifest([]);

        $html = $this->renderBlock([
            'motion_asset_id' => 'medical-hero-intro',
            'fallback_image' => '/images/medical-hero-static.jpg',
        ]);

        $this->assertStringContainsString('<img', $html);
        $this->assertStringContainsString('medical-hero-static.jpg', $html);
        $this->assertStringContainsString('tb-module-motion-fallback', $html);
    }

    // ── no license / platform unreachable → renders nothing, never throws ──

    public function test_no_license_renders_nothing_without_throwing(): void
    {
        License::query()->delete();
        Http::fake(); // any call would be unexpected; no manifest fetch should even be attempted meaningfully

        $html = $this->renderBlock(['motion_asset_id' => 'medical-hero-intro']);

        $this->assertSame('', $html);
    }

    // ── defense in depth: a manifest leaking a forbidden field is rejected wholesale ──

    public function test_manifest_with_forbidden_ai_metadata_field_is_rejected_entirely(): void
    {
        Http::fake([
            'platform.example/api/motion-runtime/manifest*' => Http::response([
                'available' => true,
                'schema_version' => '1.0',
                'site_reference' => 'site-1',
                'manifest_checksum' => str_repeat('a', 64),
                'assets' => [[
                    'asset_id' => 'medical-hero-intro',
                    'published_version' => 1,
                    'checksum' => str_repeat('b', 64),
                    'asset_type' => 'svg',
                    'optimized_reference' => '/motion/medical-hero-intro/v1/a.svg',
                    'ai_metadata' => ['prompt' => 'should never appear'],
                ]],
            ], 200),
        ]);

        $html = $this->renderBlock(['motion_asset_id' => 'medical-hero-intro']);

        $this->assertSame('', $html);
    }

    // ── #11 non-motion blocks remain completely unaffected ─────────────────

    public function test_ordinary_blocks_are_unaffected_by_the_motion_integration(): void
    {
        $html = $this->renderer->renderBlock([
            'type' => 'heading',
            'id' => 'h1',
            'props' => ['level' => 2, 'text' => 'عنوان تست'],
        ]);

        $this->assertStringContainsString('tb-heading', $html);
        $this->assertStringContainsString('عنوان تست', $html);
    }
}

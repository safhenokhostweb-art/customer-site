<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\License;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Theme Builder Motion Picker — Phase 3 (customer-site).
 *
 * ThemeDesignController::canvasMotionAssets() is a thin passthrough over
 * the existing MotionRuntimeManifestReader — it contains no query of its
 * own. These tests prove the picker's data source is exactly this site's
 * own already-authorized manifest (never the master library, never
 * another site's assets), and that the canvas editor page itself still
 * renders correctly with the new motion-picker UI wired in.
 */
class MotionPickerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        config(['site.owner_site' => true]);
        config(['site.platform_url' => 'https://platform.example']);
        Cache::forget('motion.runtime.manifest');
        License::create([
            'license_key' => 'MOTION-PICKER-KEY',
            'domain' => null,
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => ['shop', 'blog'],
        ]);
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function fakeManifest(array $assets, ?array $locked = null): void
    {
        Http::fake([
            'platform.example/api/motion-runtime/manifest*' => Http::response([
                'available' => true,
                'schema_version' => '1.0',
                'site_reference' => 'site-1',
                'manifest_checksum' => str_repeat('a', 64),
                'assets' => $assets,
                ...($locked === null ? [] : ['locked_assets' => $locked]),
            ], 200),
        ]);
    }

    // ── 2026-11-06: why a placed motion shows nothing ───────────────────

    public function test_a_premium_locked_motion_is_reported_with_its_reason(): void
    {
        $this->fakeManifest([], [
            ['asset_id' => '6fea28d1-7b83-4c09-8bc2-35e7bb061ae7', 'reason' => 'premium_license_required'],
        ]);

        $response = $this->actingAs($this->admin())
            ->getJson(route('admin.theme-design.canvas-motion-assets'));

        $response->assertOk();
        $this->assertSame([], $response->json('assets'), 'a locked motion is never offered as an asset');
        $this->assertSame([['asset_id' => '6fea28d1-7b83-4c09-8bc2-35e7bb061ae7', 'reason' => 'premium_license_required']], $response->json('locked'));
    }

    public function test_malformed_or_unknown_locked_entries_are_dropped_without_rejecting_the_manifest(): void
    {
        $this->fakeManifest([[
            'asset_id' => 'medical-hero-intro', 'published_version' => 1, 'checksum' => str_repeat('b', 64),
            'asset_type' => 'svg', 'optimized_reference' => '/motion/medical-hero-intro/v1/a.svg',
        ]], [
            ['asset_id' => 'ok-id', 'reason' => 'premium_license_required'],
            ['asset_id' => 'ok-id-2', 'reason' => 'internal detail'],
            ['asset_id' => '../etc', 'reason' => 'premium_license_required'],
            'not-an-array',
        ]);

        $response = $this->actingAs($this->admin())
            ->getJson(route('admin.theme-design.canvas-motion-assets'));

        $response->assertOk();
        $this->assertCount(1, $response->json('assets'));
        $this->assertSame([['asset_id' => 'ok-id', 'reason' => 'premium_license_required']], $response->json('locked'));
    }

    public function test_a_platform_without_locked_assets_yields_an_empty_list(): void
    {
        $this->fakeManifest([]);

        $response = $this->actingAs($this->admin())
            ->getJson(route('admin.theme-design.canvas-motion-assets'));

        $response->assertOk();
        $this->assertSame([], $response->json('locked'));
    }

    public function test_the_editor_ships_the_premium_license_notice(): void
    {
        $this->fakeManifest([]);
        Setting::set('active_canvas_theme_data', json_encode(['header_blocks' => [], 'footer_blocks' => [], 'global_styles' => [], 'pages' => [
            'home' => ['slug' => '', 'title' => 'صفحه اصلی', 'blocks' => []],
        ]], JSON_UNESCAPED_UNICODE), 'theme');

        $this->actingAs($this->admin())->get(route('admin.theme-design.canvas-edit'))
            ->assertOk()
            ->assertSee('این موشن نیازمند لایسنس پرمیوم است.', false)
            ->assertSee('tb-motion-params-notice', false);
    }

    // ── #2 the picker's data source is exactly the site's own manifest ────

    public function test_motion_assets_endpoint_returns_only_safe_fields_from_the_site_manifest(): void
    {
        $this->fakeManifest([[
            'asset_id' => 'medical-hero-intro',
            'published_version' => 1,
            'checksum' => str_repeat('b', 64),
            'asset_type' => 'svg',
            'optimized_reference' => '/motion/medical-hero-intro/v1/a.svg',
            'name' => 'Medical Hero Intro',
            'category' => 'medical',
        ]]);

        $response = $this->actingAs($this->admin())
            ->getJson(route('admin.theme-design.canvas-motion-assets'));

        $response->assertOk();
        $assets = $response->json('assets');
        $this->assertCount(1, $assets);
        $this->assertSame('medical-hero-intro', $assets[0]['asset_id']);
        $this->assertSame('Medical Hero Intro', $assets[0]['name']);
        $this->assertSame('medical', $assets[0]['category']);
        $this->assertStringContainsString('medical-hero-intro/v1/a.svg', $assets[0]['thumbnail']);
    }

    // ── #4 unpublished assets never appear ──────────────────────────────

    public function test_motion_assets_endpoint_is_empty_when_manifest_has_no_assets(): void
    {
        $this->fakeManifest([]);

        $response = $this->actingAs($this->admin())
            ->getJson(route('admin.theme-design.canvas-motion-assets'));

        $response->assertOk();
        $this->assertSame([], $response->json('assets'));
    }

    // ── #5 revoked/unassigned site (no license / manifest unavailable) ─────

    public function test_motion_assets_endpoint_is_empty_when_the_manifest_is_unavailable(): void
    {
        License::query()->delete();
        Http::fake();

        $response = $this->actingAs($this->admin())
            ->getJson(route('admin.theme-design.canvas-motion-assets'));

        $response->assertOk();
        $this->assertSame([], $response->json('assets'));
    }

    // ── #6 no sensitive metadata reaches the picker payload ─────────────

    public function test_motion_assets_endpoint_never_leaks_forbidden_fields_even_if_upstream_tries(): void
    {
        // The upstream manifest is malformed on purpose (carries a forbidden
        // field) — MotionRuntimeManifestReader itself must reject the whole
        // manifest, so the picker sees nothing rather than leaking anything.
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

        $response = $this->actingAs($this->admin())
            ->getJson(route('admin.theme-design.canvas-motion-assets'));

        $response->assertOk();
        $this->assertSame([], $response->json('assets'));
        $this->assertStringNotContainsString('ai_metadata', $response->getContent());
        $this->assertStringNotContainsString('should never appear', $response->getContent());
    }

    // ── #11 Clinic Premium's canvas editor renders with the picker wired in ──

    public function test_canvas_editor_page_renders_with_a_motion_block_present(): void
    {
        $this->fakeManifest([]);
        $themeData = [
            'header_blocks' => [],
            'footer_blocks' => [],
            'global_styles' => [],
            'pages' => [
                'home' => [
                    'slug' => '', 'title' => 'صفحه اصلی',
                    'blocks' => [
                        ['id' => 'm1', 'type' => 'module:motion', 'props' => [
                            'motion_asset_id' => 'medical-hero-intro', 'placement' => 'hero', 'enabled' => true,
                        ]],
                    ],
                ],
            ],
        ];
        Setting::set('active_canvas_theme_data', json_encode($themeData, JSON_UNESCAPED_UNICODE), 'theme');

        $response = $this->actingAs($this->admin())
            ->get(route('admin.theme-design.canvas-edit'));

        $response->assertOk();
        $response->assertSee('انتخاب موشن گرافیک', false);
    }

    // ── #12 non-motion blocks / pages remain entirely unaffected ────────

    public function test_canvas_editor_renders_normally_with_no_motion_blocks_at_all(): void
    {
        $themeData = [
            'header_blocks' => [],
            'footer_blocks' => [],
            'global_styles' => [],
            'pages' => [
                'home' => ['slug' => '', 'title' => 'صفحه اصلی', 'blocks' => [
                    ['id' => 'h1', 'type' => 'heading', 'props' => ['text' => 'سلام']],
                ]],
            ],
        ];
        Setting::set('active_canvas_theme_data', json_encode($themeData, JSON_UNESCAPED_UNICODE), 'theme');

        $response = $this->actingAs($this->admin())
            ->get(route('admin.theme-design.canvas-edit'));

        $response->assertOk();
    }
}

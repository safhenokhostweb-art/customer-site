<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\Setting;
use App\Models\SiteTheme;
use App\Models\User;
use App\Services\AssetProvisioning\AssetEntitlementClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use SaaSPlatform\ThemeRuntime\Access\SiteAssetEntitlements;
use Tests\Concerns\SeedsValidLicenseWithShop;
use Tests\TestCase;

class ThemeIsolationAndMultiOwnershipTest extends TestCase
{
    use RefreshDatabase, SeedsValidLicenseWithShop;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $license = $this->seedValidLicense(['shop', 'blog']);
        $license->forceFill(['license_key' => 'TEST-KEY-ISOLATION-001'])->save();
        config(['site.platform_url' => 'https://platform.test']);
        config(['services.customer_provision_secret' => 'test-secret-xyz']);
        Cache::forget(AssetEntitlementClient::CACHE_KEY);
        Cache::forget('platform_site_config');
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_fresh_site_without_theme_shows_no_theme_and_hides_solar_calc_option(): void
    {
        Http::fake([
            'https://platform.test/api/license/site-config*' => Http::response([
                'theme_builder' => [
                    'header_blocks' => [],
                    'footer_blocks' => [],
                    'global_styles' => [],
                    'settings' => [],
                    'meta' => [
                        'id' => 'default',
                        'name' => 'بدون قالب (تم پیش‌فرض پایه)',
                        'version' => '1.0.0',
                    ],
                ],
            ], 200),
            'https://platform.test/api/asset-entitlements*' => Http::response([
                'available' => true,
                'schema' => SiteAssetEntitlements::PAYLOAD_SCHEMA,
                'vertical' => null,
                'entitlement_keys' => [],
                'grants' => [],
                'overrides' => [],
            ], 200),
        ]);

        // 1. Settings page shows "بدون قالب (تم پیشفرض پایه)" with no solar-industry traces
        $settingsResponse = $this->actingAs($this->admin)->get(route('admin.settings.index'));
        $settingsResponse->assertOk();
        $settingsResponse->assertSee('بدون قالب (تم پیشفرض پایه)');
        $settingsResponse->assertDontSee('solar-industry');
        $settingsResponse->assertDontSee('1.1.0');

        // 2. Opening blank Canvas editor hides show_calc_btn and solar-* blocks
        $canvasResponse = $this->actingAs($this->admin)->get(route('admin.theme-design.canvas-edit', ['create_blank' => 1]));
        $canvasResponse->assertOk();
        $registry = $canvasResponse->viewData('registry');
        $this->assertArrayHasKey('nav_header', $registry);
        $this->assertArrayNotHasKey('show_calc_btn', $registry['nav_header']['schema']);
        $this->assertArrayNotHasKey('solar-energy-cycle', $registry);
    }

    public function test_grant_unlocks_solar_calc_option_in_header_inspector(): void
    {
        Http::fake([
            'https://platform.test/api/asset-entitlements*' => Http::response([
                'available' => true,
                'schema' => SiteAssetEntitlements::PAYLOAD_SCHEMA,
                'vertical' => null,
                'entitlement_keys' => [],
                'grants' => ['widget:solar_calculator'],
                'overrides' => [],
            ], 200),
        ]);

        $canvasResponse = $this->actingAs($this->admin)->get(route('admin.theme-design.canvas-edit', ['create_blank' => 1]));
        $canvasResponse->assertOk();
        $registry = $canvasResponse->viewData('registry');
        $this->assertArrayHasKey('show_calc_btn', $registry['nav_header']['schema']);
    }

    public function test_installing_second_theme_preserves_first_theme_and_allows_switching(): void
    {
        // Install first theme
        $this->postJson('/builder/api/theme/install', [
            'theme_id' => 'theme-alpha',
            'theme_name' => 'قالب آلفا',
            'source_theme_id' => '10',
            'source_theme_version' => '1.0.0',
            'source_release_id' => 100,
            'pages' => [
                'home' => ['slug' => '', 'title' => 'خانه آلفا', 'is_home' => true, 'blocks' => [
                    ['id' => 'blk-alpha-1', 'type' => 'heading', 'props' => ['text' => 'صفحه اصلی آلفا']],
                ]],
            ],
        ], ['X-Provision-Secret' => 'test-secret-xyz'])->assertOk();

        // Install second theme
        $this->postJson('/builder/api/theme/install', [
            'theme_id' => 'theme-beta',
            'theme_name' => 'قالب بتا',
            'source_theme_id' => '20',
            'source_theme_version' => '2.0.0',
            'source_release_id' => 200,
            'pages' => [
                'home' => ['slug' => '', 'title' => 'خانه بتا', 'is_home' => true, 'blocks' => [
                    ['id' => 'blk-beta-1', 'type' => 'heading', 'props' => ['text' => 'صفحه اصلی بتا']],
                ]],
            ],
        ], ['X-Provision-Secret' => 'test-secret-xyz'])->assertOk();

        // Both themes exist in site_themes; beta is active, alpha is inactive
        $this->assertDatabaseHas('site_themes', ['theme_slug' => 'theme-alpha', 'is_active' => false]);
        $this->assertDatabaseHas('site_themes', ['theme_slug' => 'theme-beta', 'is_active' => true]);
        $this->assertSame(2, SiteTheme::count());
        $this->assertSame('theme-beta', Setting::get('active_canvas_theme_slug'));

        // My Themes section in admin/theme-design lists both themes
        $designResp = $this->actingAs($this->admin)->get(route('admin.theme-design.index'));
        $designResp->assertOk();
        $designResp->assertSee('قالب آلفا');
        $designResp->assertSee('قالب بتا');
        $designResp->assertSee('فعالسازی');

        // Activate first theme again
        $this->actingAs($this->admin)
            ->post(route('admin.theme-design.my-themes.activate', 'theme-alpha'))
            ->assertRedirect();

        $this->assertDatabaseHas('site_themes', ['theme_slug' => 'theme-alpha', 'is_active' => true]);
        $this->assertDatabaseHas('site_themes', ['theme_slug' => 'theme-beta', 'is_active' => false]);
        $this->assertSame('theme-alpha', Setting::get('active_canvas_theme_slug'));
        $activeData = json_decode((string) Setting::get('active_canvas_theme_data'), true);
        $this->assertSame('صفحه اصلی آلفا', $activeData['pages']['home']['blocks'][0]['props']['text'] ?? null);
    }
}

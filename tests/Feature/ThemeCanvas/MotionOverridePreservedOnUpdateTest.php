<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\License;
use App\Models\Setting;
use App\Models\User;
use App\Services\PlatformApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Theme Builder Motion Picker — Phase 5 (template default vs customer
 * override). Reuses the update-safety guard already added to
 * ThemeMarketplaceController::install() (confirm_overwrite) — motion
 * selections are just ordinary block props inside active_canvas_theme_data,
 * so no separate override system was built; this test proves that
 * existing mechanism genuinely covers a customer's motion_asset_id choice
 * too, not just other block types.
 */
class MotionOverridePreservedOnUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        config(['site.owner_site' => true]);
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    private function admin(): User
    {
        License::create([
            'license_key' => 'MOTION-SAFETY-' . uniqid(),
            'domain' => request()->getHost(),
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => ['shop', 'blog'],
        ]);

        return User::factory()->create(['is_admin' => true]);
    }

    public function test_customer_selected_motion_asset_is_not_silently_replaced_by_a_template_update(): void
    {
        $admin = $this->admin();

        // Template default: motion_asset_id = X
        $original = ['pages' => ['home' => ['blocks' => [
            ['id' => 'm1', 'type' => 'module:motion', 'props' => ['motion_asset_id' => 'medical-hero-intro', 'enabled' => true]],
        ]]]];
        Setting::set('active_canvas_theme_data', json_encode($original), 'theme');
        Setting::set('active_canvas_theme_name', 'کلینیک پریمیوم', 'theme');

        // Customer override: motion_asset_id = Y (picked via the picker,
        // published through the canvas as normal)
        $overridden = ['pages' => ['home' => ['blocks' => [
            ['id' => 'm1', 'type' => 'module:motion', 'props' => ['motion_asset_id' => 'medical-hero-custom', 'enabled' => true]],
        ]]]];
        Setting::set('active_canvas_theme_data', json_encode($overridden), 'theme');

        // Updated template default arrives (e.g. re-downloading the theme
        // after a template update) — installing WITHOUT confirmation must
        // not silently replace the customer's Y with the new default.
        $this->mock(PlatformApiService::class, function ($mock) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('marketplaceDownloadTheme')->andReturn([
                'success' => true,
                'theme_name' => 'کلینیک پریمیوم',
                'theme_slug' => 'clinic-premium',
                'header_blocks' => [],
                'footer_blocks' => [],
                'pages' => ['home' => ['blocks' => [
                    ['id' => 'm1', 'type' => 'module:motion', 'props' => ['motion_asset_id' => 'medical-hero-v2-default', 'enabled' => true]],
                ]]],
                'global_styles' => [],
                'settings' => [],
            ]);
        });

        $response = $this->actingAs($admin)
            ->post(route('admin.theme-marketplace.install', 1));

        $response->assertSessionHas('error');

        $stored = json_decode(Setting::get('active_canvas_theme_data'), true);
        $this->assertSame(
            'medical-hero-custom',
            $stored['pages']['home']['blocks'][0]['props']['motion_asset_id'],
            'Customer override must survive an unconfirmed template update.'
        );
    }
}

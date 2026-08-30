<?php

namespace Tests\Feature;

use App\Models\License;
use App\Models\Setting;
use App\Models\User;
use App\Services\PlatformApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Theme Update Safety — the theme marketplace "install" action previously
 * behaved like an unconditional reinstall: it silently overwrote the active
 * theme and discarded any unsaved canvas draft, protected only by a single
 * backup slot that a *second* install would itself overwrite.
 *
 * Minimal fix (no merge engine): installing over an existing active theme,
 * or discarding an in-progress unsaved draft, now requires an explicit
 * `confirm_overwrite` flag on the request. A brand-new install (nothing to
 * lose yet) is unaffected. Existing backup + rollback behavior is untouched.
 */
class ThemeMarketplaceUpdateSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        // Owner-site mode bypasses EnsureLicenseValid / EnsureModuleEnabled so
        // this test can focus purely on the marketplace controller's own guard.
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
            'license_key' => 'THEME-SAFETY-' . uniqid(),
            'domain' => request()->getHost(),
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => ['shop', 'blog'],
        ]);

        return User::factory()->create(['is_admin' => true]);
    }

    private function fakeDownloadableTheme(string $themeName = 'قالب جدید'): void
    {
        $this->mock(PlatformApiService::class, function ($mock) use ($themeName) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('marketplaceDownloadTheme')->andReturn([
                'success' => true,
                'theme_name' => $themeName,
                'theme_slug' => 'new-theme',
                'header_blocks' => [],
                'footer_blocks' => [],
                'pages' => [],
                'global_styles' => [],
                'settings' => [],
            ]);
        });
    }

    // ── brand-new install: nothing to lose, no friction ─────────────────

    public function test_first_ever_install_needs_no_confirmation(): void
    {
        $admin = $this->admin();
        $this->fakeDownloadableTheme();

        $response = $this->actingAs($admin)
            ->post(route('admin.theme-marketplace.install', 1));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertNotNull(Setting::get('active_canvas_theme_data'));
    }

    // ── overwriting an active theme requires explicit confirmation ──────

    public function test_install_over_active_theme_is_blocked_without_confirmation(): void
    {
        $admin = $this->admin();
        Setting::set('active_canvas_theme_data', json_encode(['pages' => ['old']]), 'theme');
        Setting::set('active_canvas_theme_name', 'قالب قدیمی', 'theme');
        $this->fakeDownloadableTheme();

        $response = $this->actingAs($admin)
            ->post(route('admin.theme-marketplace.install', 1));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        // No silent data loss: the old theme data must be exactly as before.
        $this->assertSame(
            json_encode(['pages' => ['old']]),
            Setting::get('active_canvas_theme_data')
        );
        $this->assertNull(Setting::get('canvas_theme_backup_data'));
    }

    public function test_install_over_active_theme_proceeds_with_explicit_confirmation(): void
    {
        $admin = $this->admin();
        Setting::set('active_canvas_theme_data', json_encode(['pages' => ['old']]), 'theme');
        Setting::set('active_canvas_theme_name', 'قالب قدیمی', 'theme');
        $this->fakeDownloadableTheme('قالب جدید');

        $response = $this->actingAs($admin)
            ->post(route('admin.theme-marketplace.install', 1), ['confirm_overwrite' => '1']);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSame('قالب جدید', Setting::get('active_canvas_theme_name'));
        // The old theme is preserved in the backup slot, not destroyed.
        $this->assertSame(
            json_encode(['pages' => ['old']]),
            Setting::get('canvas_theme_backup_data')
        );
    }

    // ── an unsaved canvas draft is just as protected as a published theme ──

    public function test_install_discarding_an_unsaved_draft_is_blocked_without_confirmation(): void
    {
        $admin = $this->admin();
        Setting::set('canvas_draft_data', json_encode(['pages' => ['work-in-progress']]), 'theme');
        $this->fakeDownloadableTheme();

        $response = $this->actingAs($admin)
            ->post(route('admin.theme-marketplace.install', 1));

        $response->assertSessionHas('error');
        $this->assertNotNull(Setting::get('canvas_draft_data'));
    }

    // ── existing rollback behavior is preserved ─────────────────────────

    public function test_rollback_still_restores_the_backed_up_theme(): void
    {
        $admin = $this->admin();
        Setting::set('active_canvas_theme_data', json_encode(['pages' => ['old']]), 'theme');
        Setting::set('active_canvas_theme_name', 'قالب قدیمی', 'theme');
        $this->fakeDownloadableTheme('قالب جدید');

        $this->actingAs($admin)
            ->post(route('admin.theme-marketplace.install', 1), ['confirm_overwrite' => '1'])
            ->assertSessionHas('success');

        $this->assertSame('قالب جدید', Setting::get('active_canvas_theme_name'));

        $this->actingAs($admin)
            ->post(route('admin.theme-marketplace.rollback'))
            ->assertSessionHas('success');

        $this->assertSame('قالب قدیمی', Setting::get('active_canvas_theme_name'));
        $this->assertSame(
            json_encode(['pages' => ['old']]),
            Setting::get('active_canvas_theme_data')
        );
        $this->assertNull(Setting::get('canvas_theme_backup_data'));
    }
}

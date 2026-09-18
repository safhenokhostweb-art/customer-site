<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\Setting;
use App\Models\ThemeInstanceRevision;
use App\Models\ThemeSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsValidLicenseWithShop;
use Tests\TestCase;

/**
 * TASK 3/3 Phase 6.5C (BLD-24) — Appearance convergence.
 *
 * A prior audit found THREE unsynchronized "appearance" stores (legacy
 * ThemeSetting/presets, admin-dashboard-chrome colors, Canvas
 * global_styles) surfaced from one screen with no data relationship
 * between them. A deeper audit (this phase) proved ThemeSetting cannot be
 * deleted outright — it is genuinely load-bearing for (a) the classic
 * non-Canvas rendering path (themes/default/layout.blade.php reads it
 * directly) and (b) shared core-ui component tokens (--ui-*) that render
 * even on Canvas pages — and admin-dashboard-chrome colors are a
 * genuinely separate concern (the tenant-admin's own dashboard skin,
 * zero coupling to any public-facing surface, confirmed via exhaustive
 * caller audit).
 *
 * The convergence this phase actually delivers: ONE customer-facing
 * mutation authority. While a Canvas theme is active, the legacy color
 * form becomes read-only/informational (no second place to "change site
 * colors"), and every Canvas global_styles publish automatically syncs
 * ThemeSetting's matching fields — so the classic-path/core-ui-token
 * compatibility layer never drifts out of sync with what the customer
 * actually configured through the one real, canonical Canvas surface.
 */
class AppearanceConvergenceTest extends TestCase
{
    use RefreshDatabase, SeedsValidLicenseWithShop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markSiteAsInstalled();
    }

    private function admin(): User
    {
        $this->seedValidLicense([]);
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_legacy_color_form_is_read_only_when_canvas_is_active(): void
    {
        Setting::set('active_canvas_theme_data', json_encode(['pages' => []]), 'theme');
        Setting::set('active_canvas_theme_name', 'قالب تست', 'theme');

        $response = $this->actingAs($this->admin())->get(route('admin.theme-design.index'));

        $response->assertOk();
        $response->assertSee('رنگ‌ها و فونت سایت شما هم‌اکنون از');
        $response->assertDontSee('ذخیره و اعمال رنگ‌های سفارشی');
    }

    public function test_legacy_color_form_remains_the_real_editable_authority_with_no_canvas_theme_installed(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.theme-design.index'));

        $response->assertOk();
        $response->assertSee('ذخیره و اعمال رنگ‌های سفارشی');
        $response->assertDontSee('رنگ‌ها و فونت سایت شما هم‌اکنون از');
    }

    public function test_publishing_canvas_global_styles_syncs_theme_setting_and_switches_it_to_custom_mode(): void
    {
        Setting::set('canvas_draft_data', json_encode([
            'pages' => [], 'header_blocks' => [], 'footer_blocks' => [],
            'global_styles' => [
                'primary_color' => '#112233', 'secondary_color' => '#445566',
                'background_color' => '#f0f0f0', 'font_family' => 'Vazirmatn, sans-serif',
            ],
        ], JSON_UNESCAPED_UNICODE), 'theme');
        Setting::set('site_theme_preset_slug', 'ocean-blue', 'theme');

        $response = $this->actingAs($this->admin())->postJson(route('admin.theme-design.canvas-publish'));

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $settings = ThemeSetting::current();
        self::assertSame('#112233', $settings->primary_color);
        self::assertSame('#445566', $settings->secondary_color);
        self::assertSame('#f0f0f0', $settings->background_color);
        self::assertSame('Vazirmatn, sans-serif', $settings->font_family_heading);
        self::assertSame('Vazirmatn, sans-serif', $settings->font_family_body);
        self::assertSame('custom', Setting::get('site_theme_preset_slug'));

        self::assertSame(1, ThemeInstanceRevision::query()->count());
    }

    public function test_publishing_canvas_global_styles_never_touches_fields_canvas_has_no_concept_of(): void
    {
        ThemeSetting::query()->firstOrNew(['site_id' => null])->fill([
            'accent_color' => '#abcabc', 'header_bg' => '#000011', 'button_style' => 'pill',
        ])->save();

        Setting::set('canvas_draft_data', json_encode([
            'pages' => [], 'header_blocks' => [], 'footer_blocks' => [],
            'global_styles' => ['primary_color' => '#654321'],
        ], JSON_UNESCAPED_UNICODE), 'theme');

        $this->actingAs($this->admin())->postJson(route('admin.theme-design.canvas-publish'))->assertOk();

        $settings = ThemeSetting::current();
        self::assertSame('#654321', $settings->primary_color);
        self::assertSame('#abcabc', $settings->accent_color, 'must not clobber fields Canvas has no concept of');
        self::assertSame('#000011', $settings->header_bg);
        self::assertSame('pill', $settings->button_style);
    }

    public function test_dashboard_admin_colors_remain_visible_and_editable_regardless_of_canvas_state(): void
    {
        Setting::set('active_canvas_theme_data', json_encode(['pages' => []]), 'theme');

        $response = $this->actingAs($this->admin())->get(route('admin.theme-design.index'));

        $response->assertOk();
        // Section 1 (dashboard chrome colors) is a legitimately separate
        // concern (this admin's own panel skin, not public site
        // appearance) and must stay unaffected by Canvas convergence.
        $response->assertSee('ظاهر پنل مدیریت', false);
    }
}

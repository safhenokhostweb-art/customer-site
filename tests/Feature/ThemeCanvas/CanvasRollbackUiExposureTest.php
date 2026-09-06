<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\License;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 3H-B — proves the UI-exposure side of the rollback convergence:
 * the canonical, permanent, append-only revision history/rollback panel is
 * now reachable from the Canvas dashboard, and the two legacy rollback UIs
 * (single-slot canvas_theme_backup_*, 5-slot canvas_revision_{1..5}) are no
 * longer offered as an ordinary UI choice anywhere. Their backend routes/
 * controllers/Settings are untouched — see CanvasRevisionHistoryTest (still
 * green) for proof the legacy backend keeps working — this test only
 * covers what a normal admin session can now click.
 */
class CanvasRollbackUiExposureTest extends TestCase
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
            'license_key' => 'ROLLBACK-UI-TEST-' . uniqid(),
            'domain' => request()->getHost(),
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => ['shop', 'blog'],
        ]);

        return User::factory()->create(['is_admin' => true]);
    }

    private function activateCanvasTheme(): void
    {
        Setting::set('active_canvas_theme_data', json_encode([
            'header_blocks' => [],
            'footer_blocks' => [],
            'pages' => ['home' => ['title' => 'خانه', 'slug' => '', 'blocks' => []]],
            'global_styles' => [],
        ]), 'theme');
        Setting::set('active_canvas_theme_name', 'قالب تست', 'theme');
    }

    public function test_canvas_dashboard_exposes_canonical_revision_history_panel(): void
    {
        $this->activateCanvasTheme();

        $html = $this->actingAs($this->admin())
            ->get(route('admin.theme-design.canvas'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(route('admin.theme-design.canvas-instance-revisions'), $html);
        $this->assertStringContainsString('تاریخچه و بازیابی نسخه‌ها', $html);
    }

    public function test_canvas_dashboard_no_longer_exposes_legacy_single_slot_rollback_button(): void
    {
        $this->activateCanvasTheme();
        Setting::set('canvas_theme_backup_data', json_encode(['pages' => []]), 'theme');
        Setting::set('canvas_theme_backup_name', 'قالب قبلی', 'theme');
        Setting::set('canvas_theme_backup_at', now()->toDateTimeString(), 'theme');

        $html = $this->actingAs($this->admin())
            ->get(route('admin.theme-design.canvas'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString(route('admin.theme-marketplace.rollback'), $html);
        $this->assertStringNotContainsString('بازگردانی «قالب قبلی»', $html);
    }

    // Note: my-licenses.blade.php's own legacy-rollback-button removal is
    // verified by direct code review (its controller action calls out to
    // the live SaaS marketplace API even in this test environment, so an
    // HTTP feature test here would depend on network mocking infrastructure
    // this suite doesn't otherwise have — out of scope to add for a single
    // Blade block removal).

    public function test_appearance_index_no_longer_exposes_legacy_five_slot_revision_ui(): void
    {
        $this->activateCanvasTheme();
        Setting::set('canvas_revision_1', json_encode(['pages' => ['v1']]), 'theme');

        $html = $this->actingAs($this->admin())
            ->get(route('admin.theme-design.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString(route('admin.theme-design.canvas-revisions'), $html);
        $this->assertStringNotContainsString('تاریخچه نسخه‌های Canvas', $html);
    }

    public function test_canonical_revision_list_endpoint_used_by_the_new_panel_is_reachable(): void
    {
        $this->activateCanvasTheme();
        $admin = $this->admin();

        // canvasTheme() (the install path) already records revision #1 via
        // the backfill inside listForCurrentTheme() — confirms the exact
        // endpoint the new panel calls returns real, permanent history.
        $response = $this->actingAs($admin)->getJson(route('admin.theme-design.canvas-instance-revisions'));

        $response->assertOk();
        $this->assertNotEmpty($response->json('revisions'));
    }
}

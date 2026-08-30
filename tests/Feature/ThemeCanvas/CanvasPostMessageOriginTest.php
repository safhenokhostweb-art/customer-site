<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\License;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 3C — the canvas editor <-> live-preview iframe bridge posted and
 * accepted postMessage() traffic with target/origin '*' in both
 * directions. Both frames are always same-origin (the preview is served
 * from this app's own route), so '*' bought nothing and meant a message
 * could be delivered to (or accepted from) an unexpected origin if either
 * frame were ever navigated away. These are regression guards against the
 * vulnerable pattern reappearing — no headless-browser tooling exists in
 * this repo to exercise the actual runtime message exchange (see the
 * Phase 3A audit's browser/responsive findings).
 */
class CanvasPostMessageOriginTest extends TestCase
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
            'license_key' => 'POSTMSG-ORIGIN-' . uniqid(),
            'domain' => request()->getHost(),
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => ['shop', 'blog'],
        ]);

        return User::factory()->create(['is_admin' => true]);
    }

    public function test_canvas_editor_page_never_targets_or_accepts_wildcard_postmessage(): void
    {
        Setting::set('active_canvas_theme_data', json_encode(['pages' => ['home' => ['blocks' => []]]]), 'theme');

        $response = $this->actingAs($this->admin())->get(route('admin.theme-design.canvas-edit'));

        $response->assertOk();
        $response->assertDontSee("postMessage(msg, '*')", false);
        $response->assertSee('postMessage(msg, window.location.origin)', false);
        $response->assertSee('e.origin !== window.location.origin', false);
    }

    public function test_live_preview_iframe_never_targets_or_accepts_wildcard_postmessage(): void
    {
        Setting::set('active_canvas_theme_data', json_encode(['pages' => ['home' => ['blocks' => []]]]), 'theme');

        $response = $this->actingAs($this->admin())
            ->get(route('admin.theme-design.canvas-live-preview', ['page' => 'home']));

        $response->assertOk();
        $response->assertDontSee("postMessage(msg, '*')", false);
        $response->assertSee('postMessage(msg, window.location.origin)', false);
        $response->assertSee('e.origin !== window.location.origin', false);
    }
}

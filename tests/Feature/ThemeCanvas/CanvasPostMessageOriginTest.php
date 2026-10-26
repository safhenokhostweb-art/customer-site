<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\License;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\EnsuresInstallationLock;
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
 *
 * Phase 7 (Smart Public Rendering) corrected two assertions here. Both had
 * pinned the exact JavaScript *identifier* used when the bridge was written,
 * and the code has since been refactored behind the shared runtime contract:
 *
 *   - OUTBOUND lives in the theme-runtime package
 *     (`canvas/runtime.blade.php`), whose `post()` helper is
 *     `postMessage(message(action, payload), window.location.origin)`. The old
 *     assertion expected a local variable literally named `msg`, so it failed
 *     the moment the payload helper was reused — while the security property
 *     it was protecting (a non-'*' target origin) was still fully intact.
 *   - INBOUND is guarded by the shared `ThemeCanvasRuntime.sameOrigin(e.origin)`
 *     plus a `e.source` identity check, not by an inline
 *     `e.origin !== window.location.origin` expression.
 *
 * These assertions are therefore deliberately written against the *origin
 * argument* and the *guard function*, not against a variable name that an
 * unrelated refactor is free to rename. Asserting a literal identifier would
 * trade a real security guard for a brittle one.
 */
class CanvasPostMessageOriginTest extends TestCase
{
    use EnsuresInstallationLock, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureInstallationLock();
        config(['site.owner_site' => true]);
    }

    protected function tearDown(): void
    {
        $this->releaseInstallationLock();
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

    /**
     * @param  \Illuminate\Testing\TestResponse  $response
     */
    private function assertBridgeIsOriginPinned($response): void
    {
        // Outbound: the shared runtime's post() must always target
        // window.location.origin explicitly. Pinned on the origin argument so a
        // refactor of the payload expression cannot silently reintroduce '*'.
        $response->assertSee(
            'target.postMessage(message(action, payload), window.location.origin)',
            false
        );

        // Inbound: both frames must validate the sender before acting on it.
        // sameOrigin() is the shared authority (canvas/runtime.blade.php); the
        // e.source identity check stops another frame on the same origin from
        // impersonating the editor.
        $response->assertSee('ThemeCanvasRuntime.sameOrigin(e.origin)', false);
        $response->assertSee('e.source !==', false);

        // The vulnerable pattern itself must stay gone, in both directions.
        $response->assertDontSee("postMessage(msg, '*')", false);
        $response->assertDontSee("postMessage({ type:'select',idx}, '*')", false);
    }

    public function test_canvas_editor_page_never_targets_or_accepts_wildcard_postmessage(): void
    {
        Setting::set('active_canvas_theme_data', json_encode(['pages' => ['home' => ['blocks' => []]]]), 'theme');

        $response = $this->actingAs($this->admin())->get(route('admin.theme-design.canvas-edit'));

        $response->assertOk();
        $this->assertBridgeIsOriginPinned($response);
    }

    #[Group('canvas-homepage-baseline-2026-10-02')]
    public function test_live_preview_iframe_never_targets_or_accepts_wildcard_postmessage(): void
    {
        Setting::set('active_canvas_theme_data', json_encode(['pages' => ['home' => ['blocks' => []]]]), 'theme');

        $response = $this->actingAs($this->admin())
            ->get(route('admin.theme-design.canvas-live-preview', ['page' => 'home']));

        $response->assertOk();
        $this->assertBridgeIsOriginPinned($response);
    }
}

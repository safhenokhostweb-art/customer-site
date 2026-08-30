<?php

namespace Tests\Feature;

use App\Models\License;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Revision History — canvas_revision_1..5 Settings rows were already written
 * on every publish (rotateRevisions()), but there was no route or UI to list
 * or restore them — a write-only, unusable safety net. This adds the missing
 * read/restore path, mirroring the existing page-builder revision pattern
 * (save current as a new revision before restoring, so restoring never loses
 * what was live).
 */
class CanvasRevisionHistoryTest extends TestCase
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
            'license_key' => 'REV-TEST-' . uniqid(),
            'domain' => request()->getHost(),
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => ['shop', 'blog'],
        ]);

        return User::factory()->create(['is_admin' => true]);
    }

    private function publish(User $admin, string $pagesLabel): void
    {
        Setting::set('canvas_draft_data', json_encode(['pages' => [$pagesLabel]]), 'theme');
        $this->actingAs($admin)->postJson(route('admin.theme-design.canvas-publish'))->assertOk();
    }

    public function test_revision_list_is_empty_before_any_publish(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->getJson(route('admin.theme-design.canvas-revisions'));

        $response->assertOk();
        $this->assertSame([], $response->json('revisions'));
    }

    public function test_publishing_twice_creates_revision_history_with_timestamps(): void
    {
        $admin = $this->admin();

        $this->publish($admin, 'v1'); // nothing active yet — no rotation happens
        $this->publish($admin, 'v2'); // v1 becomes active, then rotates into slot 1

        $response = $this->actingAs($admin)->getJson(route('admin.theme-design.canvas-revisions'));
        $response->assertOk();
        $revisions = $response->json('revisions');

        $this->assertCount(1, $revisions);
        $this->assertSame(1, $revisions[0]['slot']);
        $this->assertNotNull($revisions[0]['date']);
    }

    public function test_restoring_a_revision_swaps_it_in_and_preserves_the_previously_active_theme(): void
    {
        $admin = $this->admin();

        $this->publish($admin, 'v1');
        $this->publish($admin, 'v2');
        // Active is now v2; revision slot 1 holds v1.

        $response = $this->actingAs($admin)
            ->postJson(route('admin.theme-design.canvas-revisions-restore', 1));

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertSame(json_encode(['pages' => ['v1']]), Setting::get('active_canvas_theme_data'));
        // v2 (the version just replaced) must be preserved in history, not lost.
        $this->assertSame(json_encode(['pages' => ['v2']]), Setting::get('canvas_revision_1'));
    }

    public function test_restoring_a_nonexistent_revision_slot_fails_safely(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)
            ->postJson(route('admin.theme-design.canvas-revisions-restore', 3));

        $response->assertStatus(404);
        $response->assertJson(['success' => false]);
    }
}

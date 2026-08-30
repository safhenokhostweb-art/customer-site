<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\License;
use App\Models\Setting;
use App\Models\ThemeInstanceRevision;
use App\Models\User;
use App\Services\PlatformApiService;
use App\Services\ThemeCanvas\ThemeInstanceRevisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Permanent Theme Version History (Phase 1) — theme_instance_revisions is
 * the new source of truth for recoverability, replacing the single
 * overwritable backup slot and the 5-slot rotating window as the thing
 * customers/support actually rely on. Every install, update, publish, and
 * rollback creates one more immutable row; nothing is ever deleted or
 * overwritten, and rollback creates a new row rather than rewinding.
 */
class ThemeInstanceRevisionTest extends TestCase
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
            'license_key' => 'REV-HISTORY-' . uniqid(),
            'domain' => request()->getHost(),
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => ['shop', 'blog'],
        ]);

        return User::factory()->create(['is_admin' => true]);
    }

    private function themePayload(string $slug, string $name, int $themeId, array $pages = []): array
    {
        return [
            'success' => true,
            'theme_id' => $themeId,
            'theme_name' => $name,
            'theme_slug' => $slug,
            'header_blocks' => [],
            'footer_blocks' => [],
            'pages' => $pages,
            'global_styles' => [],
            'settings' => [],
        ];
    }

    /**
     * A single mock covering every install() call in the test, returning
     * queued payloads in order. Mockery's handling of repeated shouldReceive
     * setup for the same unconstrained method is unreliable across separate
     * $this->mock() calls, so every download this test needs must be queued
     * up front through one mock instance.
     */
    private function mockPlatformDownloads(array $payloads): void
    {
        $queue = $payloads;
        $this->mock(PlatformApiService::class, function ($mock) use (&$queue) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('marketplaceDownloadTheme')->andReturnUsing(function () use (&$queue) {
                return array_shift($queue);
            });
        });
    }

    // ── 1. Install theme -> revision #1 ─────────────────────────────────

    public function test_install_creates_revision_one(): void
    {
        $admin = $this->admin();
        $this->mockPlatformDownloads([
            $this->themePayload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => ['v1']]]),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.theme-marketplace.install', 1))
            ->assertSessionHas('success');

        $revisions = ThemeInstanceRevision::where('theme_identifier', 'clinic-premium')->get();
        $this->assertCount(1, $revisions);
        $this->assertSame(1, $revisions[0]->revision_number);
        $this->assertSame(ThemeInstanceRevision::TYPE_INSTALL, $revisions[0]->type);
        $this->assertSame(['home' => ['blocks' => ['v1']]], $revisions[0]->theme_data['pages']);
        $this->assertSame($revisions[0]->id, (int) Setting::get('active_theme_revision_id'));
    }

    // ── 2. Customer customization publish -> revision #2 ────────────────

    public function test_publish_after_install_creates_revision_two(): void
    {
        $admin = $this->admin();
        $this->mockPlatformDownloads([
            $this->themePayload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => ['v1']]]),
        ]);
        $this->actingAs($admin)->post(route('admin.theme-marketplace.install', 1));

        Setting::set('canvas_draft_data', json_encode(['pages' => ['home' => ['blocks' => ['v2-customized']]]]), 'theme');
        $this->actingAs($admin)->postJson(route('admin.theme-design.canvas-publish'))->assertOk();

        $revisions = ThemeInstanceRevision::where('theme_identifier', 'clinic-premium')->orderBy('revision_number')->get();
        $this->assertCount(2, $revisions);
        $this->assertSame(2, $revisions[1]->revision_number);
        $this->assertSame(ThemeInstanceRevision::TYPE_PUBLISH, $revisions[1]->type);
        $this->assertSame(['v2-customized'], $revisions[1]->theme_data['pages']['home']['blocks']);

        // Live state matches the new revision.
        $stored = json_decode(Setting::get('active_canvas_theme_data'), true);
        $this->assertSame(['v2-customized'], $stored['pages']['home']['blocks']);
    }

    // ── 3. Second update/install -> revision #3, old revisions preserved ─

    public function test_second_install_creates_revision_three_and_keeps_history(): void
    {
        $admin = $this->admin();
        $this->mockPlatformDownloads([
            $this->themePayload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => ['v1']]]),
            $this->themePayload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => ['v3-update']]]),
        ]);
        $this->actingAs($admin)->post(route('admin.theme-marketplace.install', 1));

        Setting::set('canvas_draft_data', json_encode(['pages' => ['home' => ['blocks' => ['v2']]]]), 'theme');
        $this->actingAs($admin)->postJson(route('admin.theme-design.canvas-publish'));

        $this->actingAs($admin)
            ->post(route('admin.theme-marketplace.install', 1), ['confirm_overwrite' => '1'])
            ->assertSessionHas('success');

        $revisions = ThemeInstanceRevision::where('theme_identifier', 'clinic-premium')->orderBy('revision_number')->get();
        $this->assertCount(3, $revisions);
        $this->assertSame([1, 2, 3], $revisions->pluck('revision_number')->all());
        $this->assertSame([
            ThemeInstanceRevision::TYPE_INSTALL,
            ThemeInstanceRevision::TYPE_PUBLISH,
            ThemeInstanceRevision::TYPE_UPDATE,
        ], $revisions->pluck('type')->all());
        // Old revisions untouched, and the update actually carried new content.
        $this->assertSame(['v1'], $revisions[0]->theme_data['pages']['home']['blocks']);
        $this->assertSame(['v2'], $revisions[1]->theme_data['pages']['home']['blocks']);
        $this->assertSame(['v3-update'], $revisions[2]->theme_data['pages']['home']['blocks']);
    }

    // ── 4. Rollback to revision #1 -> new rollback revision, history intact ─

    public function test_rollback_to_first_revision_creates_new_revision_without_deleting_history(): void
    {
        $admin = $this->admin();
        $this->mockPlatformDownloads([
            $this->themePayload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => ['v1']]]),
        ]);
        $this->actingAs($admin)->post(route('admin.theme-marketplace.install', 1));
        $firstRevisionId = ThemeInstanceRevision::where('theme_identifier', 'clinic-premium')->sole()->id;

        Setting::set('canvas_draft_data', json_encode(['pages' => ['home' => ['blocks' => ['v2']]]]), 'theme');
        $this->actingAs($admin)->postJson(route('admin.theme-design.canvas-publish'));

        $response = $this->actingAs($admin)
            ->postJson(route('admin.theme-design.canvas-instance-revisions-rollback', $firstRevisionId));

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $revisions = ThemeInstanceRevision::where('theme_identifier', 'clinic-premium')->orderBy('revision_number')->get();
        $this->assertCount(3, $revisions, 'rollback must add a revision, not remove or overwrite any');
        $this->assertSame(ThemeInstanceRevision::TYPE_ROLLBACK_SNAPSHOT, $revisions[2]->type);
        $this->assertSame(['v1'], $revisions[2]->theme_data['pages']['home']['blocks']);
        $this->assertSame($firstRevisionId, $revisions[2]->metadata['restored_from_revision_id']);

        // Original revisions #1 and #2 both still exist, unmodified.
        $this->assertSame(['v1'], $revisions[0]->theme_data['pages']['home']['blocks']);
        $this->assertSame(['v2'], $revisions[1]->theme_data['pages']['home']['blocks']);

        // Live state now reflects the restored data.
        $stored = json_decode(Setting::get('active_canvas_theme_data'), true);
        $this->assertSame(['v1'], $stored['pages']['home']['blocks']);
        $this->assertSame($revisions[2]->id, (int) Setting::get('active_theme_revision_id'));
    }

    // ── 5. Second rollback still works ───────────────────────────────────

    public function test_a_second_rollback_still_works(): void
    {
        $admin = $this->admin();
        $this->mockPlatformDownloads([
            $this->themePayload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => ['v1']]]),
        ]);
        $this->actingAs($admin)->post(route('admin.theme-marketplace.install', 1));
        $firstId = ThemeInstanceRevision::where('theme_identifier', 'clinic-premium')->sole()->id;

        Setting::set('canvas_draft_data', json_encode(['pages' => ['home' => ['blocks' => ['v2']]]]), 'theme');
        $this->actingAs($admin)->postJson(route('admin.theme-design.canvas-publish'));
        $secondId = ThemeInstanceRevision::where('theme_identifier', 'clinic-premium')->where('revision_number', 2)->sole()->id;

        $this->actingAs($admin)
            ->postJson(route('admin.theme-design.canvas-instance-revisions-rollback', $firstId))
            ->assertOk();

        // Roll forward again to revision #2's content.
        $response = $this->actingAs($admin)
            ->postJson(route('admin.theme-design.canvas-instance-revisions-rollback', $secondId));

        $response->assertOk();
        $revisions = ThemeInstanceRevision::where('theme_identifier', 'clinic-premium')->orderBy('revision_number')->get();
        $this->assertCount(4, $revisions);
        $this->assertSame(['v2'], $revisions[3]->theme_data['pages']['home']['blocks']);

        $stored = json_decode(Setting::get('active_canvas_theme_data'), true);
        $this->assertSame(['v2'], $stored['pages']['home']['blocks']);
    }

    // ── 6. Cross-lineage / unauthorized revision access is blocked ──────

    public function test_rolling_back_to_a_revision_from_a_different_theme_lineage_is_blocked(): void
    {
        $admin = $this->admin();
        $this->mockPlatformDownloads([
            $this->themePayload('theme-a', 'قالب الف', 1, ['home' => ['blocks' => ['a1']]]),
            $this->themePayload('theme-b', 'قالب ب', 2, ['home' => ['blocks' => ['b1']]]),
        ]);

        $this->actingAs($admin)->post(route('admin.theme-marketplace.install', 1));
        $themeARevisionId = ThemeInstanceRevision::where('theme_identifier', 'theme-a')->sole()->id;

        $this->actingAs($admin)
            ->post(route('admin.theme-marketplace.install', 2), ['confirm_overwrite' => '1'])
            ->assertSessionHas('success');
        $this->assertSame('theme-b', Setting::get('active_canvas_theme_slug'));

        // theme-b is now active; theme-a's revision must not be restorable.
        $response = $this->actingAs($admin)
            ->postJson(route('admin.theme-design.canvas-instance-revisions-rollback', $themeARevisionId));

        $response->assertStatus(404);
        $response->assertJson(['success' => false]);

        // Live state is untouched.
        $stored = json_decode(Setting::get('active_canvas_theme_data'), true);
        $this->assertSame(['b1'], $stored['pages']['home']['blocks']);

        // theme-a's own history is untouched too.
        $this->assertCount(1, ThemeInstanceRevision::where('theme_identifier', 'theme-a')->get());
    }

    public function test_rolling_back_to_a_nonexistent_revision_id_is_blocked(): void
    {
        $admin = $this->admin();
        $this->mockPlatformDownloads([
            $this->themePayload('clinic-premium', 'کلینیک پریمیوم', 1),
        ]);
        $this->actingAs($admin)->post(route('admin.theme-marketplace.install', 1));

        $response = $this->actingAs($admin)
            ->postJson(route('admin.theme-design.canvas-instance-revisions-rollback', 999999));

        $response->assertStatus(404);
        $response->assertJson(['success' => false]);
    }

    // ── 7. Existing (pre-feature) installations migrate safely ──────────

    public function test_pre_existing_active_theme_data_is_backfilled_into_revision_one(): void
    {
        $admin = $this->admin();

        // Simulate a site that was already customized before this feature
        // shipped: active_canvas_theme_data exists, but no revision rows do.
        Setting::set('active_canvas_theme_slug', 'legacy-theme', 'theme');
        Setting::set('active_canvas_theme_name', 'قالب قدیمی', 'theme');
        Setting::set('active_canvas_theme_data', json_encode(['pages' => ['home' => ['blocks' => ['legacy']]]]), 'theme');

        $this->assertSame(0, ThemeInstanceRevision::count());

        $response = $this->actingAs($admin)
            ->getJson(route('admin.theme-design.canvas-instance-revisions'));

        $response->assertOk();
        $revisions = $response->json('revisions');
        $this->assertCount(1, $revisions);
        $this->assertSame(1, $revisions[0]['revision_number']);
        $this->assertSame(ThemeInstanceRevision::TYPE_INSTALL, $revisions[0]['type']);
        $this->assertTrue($revisions[0]['is_active']);

        $stored = ThemeInstanceRevision::sole();
        $this->assertTrue($stored->metadata['backfilled']);
        $this->assertSame(['legacy'], $stored->theme_data['pages']['home']['blocks']);
        $this->assertSame($stored->id, (int) Setting::get('active_theme_revision_id'));

        // Live theme data is unchanged by the backfill.
        $liveData = json_decode(Setting::get('active_canvas_theme_data'), true);
        $this->assertSame(['legacy'], $liveData['pages']['home']['blocks']);
    }

    public function test_backfill_is_idempotent(): void
    {
        Setting::set('active_canvas_theme_slug', 'legacy-theme', 'theme');
        Setting::set('active_canvas_theme_data', json_encode(['pages' => ['home' => ['blocks' => ['legacy']]]]), 'theme');

        app(ThemeInstanceRevisionService::class)->ensureBackfilled();
        app(ThemeInstanceRevisionService::class)->ensureBackfilled();

        $this->assertCount(1, ThemeInstanceRevision::where('theme_identifier', 'legacy-theme')->get());
    }

    // ── A site with no marketplace theme at all groups under 'custom' ───

    public function test_publishing_without_a_marketplace_theme_uses_the_custom_identifier(): void
    {
        $admin = $this->admin();
        Setting::set('canvas_draft_data', json_encode(['pages' => ['home' => ['blocks' => ['from-scratch']]]]), 'theme');

        $this->actingAs($admin)->postJson(route('admin.theme-design.canvas-publish'))->assertOk();

        $revision = ThemeInstanceRevision::where('theme_identifier', 'custom')->sole();
        $this->assertSame(1, $revision->revision_number);
        $this->assertSame(['from-scratch'], $revision->theme_data['pages']['home']['blocks']);
    }
}

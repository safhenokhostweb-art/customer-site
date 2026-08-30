<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\License;
use App\Models\Setting;
use App\Models\ThemeInstanceRevision;
use App\Models\User;
use App\Services\PlatformApiService;
use App\Services\ThemeCanvas\ThemeInstanceRevisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Phase 3B — Theme Instance Revision integrity.
 *
 * Closes three proven bypasses of ThemeInstanceRevisionService's single
 * write path (canvasImport(), canvasDeactivate(), the legacy
 * ThemeMarketplaceController::rollback()), adds a transactional guarantee
 * to record() itself, and adds self-healing reconciliation for
 * installations whose active_theme_revision_id was left stale by those
 * now-closed bypasses.
 */
class ThemeInstanceRevisionIntegrityTest extends TestCase
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
            'license_key' => 'REV-INTEGRITY-' . uniqid(),
            'domain' => request()->getHost(),
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => ['shop', 'blog'],
        ]);

        return User::factory()->create(['is_admin' => true]);
    }

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

    private function importFile(array $data): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('theme.json', json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    // ── A/B. Import creates an immutable revision and correct pointer ────

    public function test_fresh_import_creates_install_revision_and_activates_it(): void
    {
        $admin = $this->admin();

        $payload = [
            'pages' => ['home' => ['blocks' => ['imported-v1']]],
            '_export_meta' => ['theme_name' => 'وارد شده', 'theme_slug' => 'imported-theme'],
        ];

        $this->actingAs($admin)
            ->post(route('admin.theme-design.canvas-import'), ['theme_file' => $this->importFile($payload)])
            ->assertSessionHas('success');

        $revisions = ThemeInstanceRevision::where('theme_identifier', 'imported-theme')->get();
        $this->assertCount(1, $revisions, 'import must land exactly one immutable revision');
        $this->assertSame(ThemeInstanceRevision::TYPE_INSTALL, $revisions[0]->type);
        $this->assertSame(['imported-v1'], $revisions[0]->theme_data['pages']['home']['blocks']);
        $this->assertSame($revisions[0]->id, (int) Setting::get('active_theme_revision_id'));
        $this->assertSame('imported-theme', Setting::get('active_canvas_theme_slug'));
        $this->assertSame('وارد شده', Setting::get('active_canvas_theme_name'));
    }

    public function test_import_over_an_active_theme_is_type_update_and_preserves_prior_history(): void
    {
        $admin = $this->admin();
        $this->mockPlatformDownloads([
            $this->themePayload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => ['v1']]]),
        ]);
        $this->actingAs($admin)->post(route('admin.theme-marketplace.install', 1));

        $payload = [
            'pages' => ['home' => ['blocks' => ['imported-v2']]],
            '_export_meta' => ['theme_name' => 'وارد شده', 'theme_slug' => 'imported-theme'],
        ];
        $this->actingAs($admin)
            ->post(route('admin.theme-design.canvas-import'), ['theme_file' => $this->importFile($payload)])
            ->assertSessionHas('success');

        // Prior lineage's history is completely untouched.
        $this->assertCount(1, ThemeInstanceRevision::where('theme_identifier', 'clinic-premium')->get());

        // Mirrors ThemeMarketplaceController::install()'s existing type
        // classification: "update" means something was active before the
        // switch, regardless of whether the NEW lineage itself has prior
        // history — not something Phase 3B changes, only preserves.
        $imported = ThemeInstanceRevision::where('theme_identifier', 'imported-theme')->sole();
        $this->assertSame(ThemeInstanceRevision::TYPE_UPDATE, $imported->type);
        $this->assertSame($imported->id, (int) Setting::get('active_theme_revision_id'));
    }

    public function test_import_never_fabricates_a_numeric_source_theme_id_from_the_placeholder_label(): void
    {
        $admin = $this->admin();

        // No _export_meta at all -> theme_id falls back to 'imported-<timestamp>',
        // which must never be coerced into the ledger's source_theme_id column.
        $payload = ['pages' => ['home' => ['blocks' => ['x']]]];

        $this->actingAs($admin)
            ->post(route('admin.theme-design.canvas-import'), ['theme_file' => $this->importFile($payload)]);

        $revision = ThemeInstanceRevision::sole();
        $this->assertNull($revision->source_theme_id);
    }

    public function test_import_clears_a_stale_draft_from_the_previous_theme(): void
    {
        $admin = $this->admin();
        Setting::set('active_canvas_theme_data', json_encode(['pages' => ['home' => ['blocks' => ['old']]]]), 'theme');
        Setting::set('canvas_draft_data', json_encode(['pages' => ['home' => ['blocks' => ['unsaved-old-edit']]]]), 'theme');

        $payload = [
            'pages' => ['home' => ['blocks' => ['imported']]],
            '_export_meta' => ['theme_slug' => 'imported-theme'],
        ];
        $this->actingAs($admin)
            ->post(route('admin.theme-design.canvas-import'), ['theme_file' => $this->importFile($payload)]);

        $this->assertEmpty(Setting::get('canvas_draft_data'), 'a foreign draft must not silently mask the just-imported theme');
    }

    // ── C. Deactivate cannot leave a stale active revision pointer ───────

    public function test_deactivate_clears_the_pointer_and_does_not_fabricate_a_revision(): void
    {
        $admin = $this->admin();
        $this->mockPlatformDownloads([
            $this->themePayload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => ['v1']]]),
        ]);
        $this->actingAs($admin)->post(route('admin.theme-marketplace.install', 1));

        $this->assertSame(1, ThemeInstanceRevision::count());

        $this->actingAs($admin)
            ->post(route('admin.theme-design.canvas-deactivate'))
            ->assertSessionHas('success');

        // No fake revision was created for "no active theme".
        $this->assertSame(1, ThemeInstanceRevision::count());

        // Pointer and identity are all cleared — nothing dangling.
        $this->assertEmpty(Setting::get('active_theme_revision_id'));
        $this->assertEmpty(Setting::get('active_canvas_theme_data'));
        $this->assertEmpty(Setting::get('active_canvas_theme_slug'));
        $this->assertEmpty(Setting::get('active_canvas_theme_name'));
        $this->assertEmpty(Setting::get('active_canvas_theme_id'));

        // The deactivated lineage's own history remains fully intact.
        $this->assertCount(1, ThemeInstanceRevision::where('theme_identifier', 'clinic-premium')->get());
    }

    public function test_reinstalling_after_deactivate_starts_a_clean_new_pointer(): void
    {
        $admin = $this->admin();
        $this->mockPlatformDownloads([
            $this->themePayload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => ['v1']]]),
            $this->themePayload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => ['v2-reinstall']]]),
        ]);
        $this->actingAs($admin)->post(route('admin.theme-marketplace.install', 1));
        $this->actingAs($admin)->post(route('admin.theme-design.canvas-deactivate'));

        $this->actingAs($admin)
            ->post(route('admin.theme-marketplace.install', 1))
            ->assertSessionHas('success');

        $revisions = ThemeInstanceRevision::where('theme_identifier', 'clinic-premium')->orderBy('revision_number')->get();
        $this->assertCount(2, $revisions, 'reinstall after deactivate must continue the same lineage history, not start a duplicate');
        $this->assertSame($revisions[1]->id, (int) Setting::get('active_theme_revision_id'));
    }

    // ── Legacy marketplace rollback keeps the ledger's own invariant ─────

    public function test_legacy_backup_rollback_keeps_active_pointer_and_lineage_in_agreement(): void
    {
        $admin = $this->admin();
        $this->mockPlatformDownloads([
            $this->themePayload('theme-a', 'قالب الف', 1, ['home' => ['blocks' => ['a1']]]),
            $this->themePayload('theme-b', 'قالب ب', 2, ['home' => ['blocks' => ['b1']]]),
        ]);
        $this->actingAs($admin)->post(route('admin.theme-marketplace.install', 1));
        $this->actingAs($admin)
            ->post(route('admin.theme-marketplace.install', 2), ['confirm_overwrite' => '1'])
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->post(route('admin.theme-marketplace.rollback'))
            ->assertSessionHas('success');

        // Restored (theme-a's original data, filed under 'custom' since the
        // legacy backup slot never tracked lineage) must agree with itself:
        // the pointer, the lineage Setting, and currentIdentifier() all
        // point at the SAME revision, so a later action on "the active
        // revision" is never wrongly rejected as cross-lineage.
        $identifier = app(ThemeInstanceRevisionService::class)->currentIdentifier();
        $activeId = (int) Setting::get('active_theme_revision_id');
        $activeRevision = ThemeInstanceRevision::find($activeId);

        $this->assertNotNull($activeRevision, 'active_theme_revision_id must point at a real row after a legacy rollback');
        $this->assertSame($identifier, $activeRevision->theme_identifier, 'pointer and currentIdentifier() must agree immediately after rollback');
        $this->assertSame(['a1'], $activeRevision->theme_data['pages']['home']['blocks']);

        // Proves the agreement is load-bearing: rolling back to the just
        // "restored" revision itself must be accepted, not 404'd as
        // cross-lineage the way the pre-fix bug would have caused.
        $this->actingAs($admin)
            ->postJson(route('admin.theme-design.canvas-instance-revisions-rollback', $activeId))
            ->assertOk();
    }

    // ── Existing-installation reconciliation: stale/foreign pointer heals ─

    public function test_stale_active_pointer_self_heals_to_latest_without_duplicating_history(): void
    {
        Setting::set('active_canvas_theme_slug', 'legacy-theme', 'theme');
        Setting::set('active_canvas_theme_data', json_encode(['pages' => ['home' => ['blocks' => ['legacy-v2']]]]), 'theme');

        // Simulate ledger rows that already exist (e.g. from before Phase
        // 3B closed the bypasses) but a pointer that was left stale/foreign
        // by one of those old bugs.
        $rev1 = ThemeInstanceRevision::create([
            'theme_identifier' => 'legacy-theme', 'revision_number' => 1,
            'type' => ThemeInstanceRevision::TYPE_INSTALL, 'theme_data' => ['pages' => ['home' => ['blocks' => ['legacy-v1']]]],
        ]);
        $rev2 = ThemeInstanceRevision::create([
            'theme_identifier' => 'legacy-theme', 'revision_number' => 2,
            'type' => ThemeInstanceRevision::TYPE_PUBLISH, 'theme_data' => ['pages' => ['home' => ['blocks' => ['legacy-v2']]]],
        ]);
        // Foreign-lineage id — never valid for 'legacy-theme'.
        $foreign = ThemeInstanceRevision::create([
            'theme_identifier' => 'other-theme', 'revision_number' => 1,
            'type' => ThemeInstanceRevision::TYPE_INSTALL, 'theme_data' => ['pages' => []],
        ]);
        Setting::set('active_theme_revision_id', $foreign->id, 'theme');

        app(ThemeInstanceRevisionService::class)->ensureBackfilled();

        $this->assertSame($rev2->id, (int) Setting::get('active_theme_revision_id'), 'pointer must heal to the latest revision of the CURRENT lineage');
        $this->assertCount(2, ThemeInstanceRevision::where('theme_identifier', 'legacy-theme')->get(), 'reconciliation must never fabricate a duplicate/backfilled revision when real history already exists');
    }

    public function test_reconciliation_is_idempotent_once_pointer_is_valid(): void
    {
        Setting::set('active_canvas_theme_slug', 'legacy-theme', 'theme');
        Setting::set('active_canvas_theme_data', json_encode(['pages' => ['home' => ['blocks' => ['v1']]]]), 'theme');
        $rev = ThemeInstanceRevision::create([
            'theme_identifier' => 'legacy-theme', 'revision_number' => 1,
            'type' => ThemeInstanceRevision::TYPE_INSTALL, 'theme_data' => ['pages' => ['home' => ['blocks' => ['v1']]]],
        ]);
        Setting::set('active_theme_revision_id', $rev->id, 'theme');

        $service = app(ThemeInstanceRevisionService::class);
        $service->ensureBackfilled();
        $service->ensureBackfilled();

        $this->assertSame($rev->id, (int) Setting::get('active_theme_revision_id'));
        $this->assertCount(1, ThemeInstanceRevision::where('theme_identifier', 'legacy-theme')->get());
    }

    // ── record() is transactional: a failure leaves live state untouched ──

    public function test_record_failure_leaves_active_state_and_pointer_unchanged(): void
    {
        $admin = $this->admin();
        $this->mockPlatformDownloads([
            $this->themePayload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => ['v1']]]),
        ]);
        $this->actingAs($admin)->post(route('admin.theme-marketplace.install', 1));

        $beforeData = Setting::get('active_canvas_theme_data');
        $beforePointer = (int) Setting::get('active_theme_revision_id');

        // Force a deterministic mid-transaction failure via a model event —
        // proves record()'s atomicity guarantee without relying on a real
        // multi-connection race, which "do not overengineer distributed
        // locking" (Phase 3B scope) rules out testing directly.
        ThemeInstanceRevision::creating(function (ThemeInstanceRevision $model) {
            if (($model->metadata['force_test_failure'] ?? false) === true) {
                throw new \RuntimeException('Forced failure for Phase 3B atomicity test.');
            }
        });

        $threw = false;
        try {
            app(ThemeInstanceRevisionService::class)->record(
                ['pages' => ['home' => ['blocks' => ['should-not-land']]]],
                ThemeInstanceRevision::TYPE_PUBLISH,
                ['theme_identifier' => 'clinic-premium', 'metadata' => ['force_test_failure' => true]]
            );
        } catch (\Throwable $e) {
            $threw = true;
        } finally {
            ThemeInstanceRevision::flushEventListeners();
        }

        $this->assertTrue($threw, 'a forced create()-time failure must surface as a thrown exception, not a silent partial write');
        $this->assertSame($beforeData, Setting::get('active_canvas_theme_data'), 'active_canvas_theme_data must be untouched when record() fails mid-transaction');
        $this->assertSame($beforePointer, (int) Setting::get('active_theme_revision_id'), 'active_theme_revision_id must be untouched when record() fails mid-transaction');
        $this->assertSame(1, ThemeInstanceRevision::where('theme_identifier', 'clinic-premium')->count(), 'the failed revision insert must not have landed either');
    }
}

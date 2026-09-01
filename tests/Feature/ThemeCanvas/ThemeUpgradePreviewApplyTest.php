<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\License;
use App\Models\Setting;
use App\Models\ThemeInstanceRevision;
use App\Models\User;
use App\Services\PlatformApiService;
use App\Services\ThemeCanvas\ThemeInstanceBaseRelease;
use App\Services\ThemeCanvas\ThemeInstanceRevisionService;
use App\Services\ThemeCanvas\ThemeUpgradePreviewResult;
use App\Services\ThemeCanvas\ThemeUpgradePreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 3D-F — the real Marketplace update flow wired to Preview + Apply:
 * BASE (from the local immutable ledger) + LOCAL (from the active revision)
 * + REMOTE (fresh Marketplace download) → reconcile → dependency-gate →
 * preview → explicit confirm → server-side recompute → atomic apply.
 */
class ThemeUpgradePreviewApplyTest extends TestCase
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

    private function admin(array $modules = ['shop', 'blog']): User
    {
        License::create([
            'license_key' => 'UPGRADE-FLOW-' . uniqid(),
            'domain' => request()->getHost(),
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => $modules,
        ]);

        return User::factory()->create(['is_admin' => true]);
    }

    private function payload(string $slug, string $name, int $themeId, array $pages, int $releaseId, string $releaseVersion, array $dependencies = []): array
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
            'release_id' => $releaseId,
            'release_version' => $releaseVersion,
            'dependencies' => $dependencies,
            'dependency_status' => $dependencies === [] ? 'unresolved' : 'resolved',
        ];
    }

    private function mockDownloadSequence(array $payloads): void
    {
        $queue = $payloads;
        $this->mock(PlatformApiService::class, function ($mock) use (&$queue) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('marketplaceDownloadTheme')->andReturnUsing(function () use (&$queue) {
                return array_shift($queue);
            });
        });
    }

    private function block(string $id, string $type, array $extra = []): array
    {
        return array_merge(['id' => $id, 'type' => $type], $extra);
    }

    /** Installs clinic-premium v1 (release 100) with a home page containing block b1, no customization. */
    private function installV1(User $admin, array $extraPayload = []): void
    {
        $this->mockDownloadSequence([array_merge(
            $this->payload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => [$this->block('b1', 'text', ['props' => ['title' => 'A']])]]], 100, '1.0.0'),
            $extraPayload
        )]);
        $this->actingAs($admin)->post(route('admin.theme-marketplace.install', 1))->assertSessionHas('success');
    }

    // ── A. preview performs zero writes ───────────────────────────────────

    public function test_a_preview_performs_zero_writes(): void
    {
        $admin = $this->admin();
        $this->installV1($admin);

        $beforeData = Setting::get('active_canvas_theme_data');
        $beforeRevisionId = Setting::get('active_theme_revision_id');
        $beforeCount = ThemeInstanceRevision::count();

        $this->mockDownloadSequence([$this->payload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => [$this->block('b1', 'text', ['props' => ['title' => 'A']])]]], 200, '2.0.0')]);
        $this->actingAs($admin)->get(route('admin.theme-marketplace.preview-upgrade', 1))->assertOk();

        $this->assertSame($beforeData, Setting::get('active_canvas_theme_data'));
        $this->assertSame($beforeRevisionId, Setting::get('active_theme_revision_id'));
        $this->assertSame($beforeCount, ThemeInstanceRevision::count());
    }

    // ── B/C/D. exact BASE/LOCAL/REMOTE sourcing ────────────────────────────

    public function test_bcd_base_local_remote_are_sourced_exactly(): void
    {
        $admin = $this->admin();
        $this->installV1($admin);

        $this->mockDownloadSequence([$this->payload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => [$this->block('b1', 'text', ['props' => ['title' => 'A']])]]], 200, '2.0.0')]);

        $preview = app(ThemeUpgradePreviewService::class)->preview(1);

        $this->assertTrue($preview->isReady());
        $this->assertSame(100, $preview->currentRelease['release_id']); // B: exact BASE (v1's release)
        $this->assertSame('1.0.0', $preview->currentRelease['release_version']);
        $this->assertSame(200, $preview->targetRelease['release_id']); // D: exact REMOTE
        $this->assertSame([], $preview->reconciliation->conflicts); // C: LOCAL correctly matched BASE+REMOTE with nothing changed
    }

    // ── E. normal LOCAL customization preserved ────────────────────────────

    public function test_e_local_customization_preserved(): void
    {
        $admin = $this->admin();
        $this->installV1($admin);

        // Customer edits the block via publish (creates a PUBLISH revision, becomes new active/LOCAL).
        Setting::set('canvas_draft_data', json_encode(['pages' => ['home' => ['blocks' => [$this->block('b1', 'text', ['props' => ['title' => 'Customer Edit']])]]]]), 'theme');
        $this->actingAs($admin)->postJson(route('admin.theme-design.canvas-publish'))->assertOk();

        $this->mockDownloadSequence([$this->payload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => [$this->block('b1', 'text', ['props' => ['title' => 'A']])]]], 200, '2.0.0')]);

        $preview = app(ThemeUpgradePreviewService::class)->preview(1);

        $this->assertTrue($preview->isReady());
        $this->assertSame('Customer Edit', $preview->reconciliation->reconciledTheme['pages']['home']['blocks'][0]['props']['title']);
        $this->assertSame([], $preview->reconciliation->conflicts);
    }

    // ── F. REMOTE additions appear ──────────────────────────────────────────

    public function test_f_remote_additions_appear(): void
    {
        $admin = $this->admin();
        $this->installV1($admin);

        $this->mockDownloadSequence([$this->payload('clinic-premium', 'کلینیک پریمیوم', 1, [
            'home' => ['blocks' => [$this->block('b1', 'text', ['props' => ['title' => 'A']]), $this->block('new_master', 'text')]],
        ], 200, '2.0.0')]);

        $preview = app(ThemeUpgradePreviewService::class)->preview(1);

        $this->assertTrue($preview->isReady());
        $this->assertContains('new_master', array_column($preview->reconciliation->reconciledTheme['pages']['home']['blocks'], 'id'));
        $this->assertNotEmpty($preview->reconciliation->remoteAdditions);
    }

    // ── G. non-blocking conflict preview remains applicable ────────────────

    public function test_g_non_blocking_conflict_still_applicable(): void
    {
        $admin = $this->admin();
        $this->installV1($admin);

        Setting::set('canvas_draft_data', json_encode(['pages' => ['home' => ['blocks' => [$this->block('b1', 'text', ['props' => ['title' => 'Customer']])]]]]), 'theme');
        $this->actingAs($admin)->postJson(route('admin.theme-design.canvas-publish'));

        $this->mockDownloadSequence([$this->payload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => [$this->block('b1', 'text', ['props' => ['title' => 'Master Changed']])]]], 200, '2.0.0')]);

        $preview = app(ThemeUpgradePreviewService::class)->preview(1);

        $this->assertTrue($preview->isReady());
        $this->assertNotEmpty($preview->reconciliation->conflicts);
        $this->assertTrue($preview->reconciliation->canApply(), 'a non-blocking conflict must never make the upgrade inapplicable');
    }

    // ── H/I/J. dependency gate at preview time ─────────────────────────────

    public function test_h_required_missing_dependency_blocks_preview(): void
    {
        $admin = $this->admin(modules: ['blog']); // no 'forms' module
        $this->installV1($admin);

        $this->mockDownloadSequence([$this->payload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => []]], 200, '2.0.0', [
            ['type' => 'module', 'identifier' => 'forms', 'is_required' => true, 'version_constraint' => null, 'metadata' => []],
        ])]);

        $preview = app(ThemeUpgradePreviewService::class)->preview(1);

        $this->assertTrue($preview->isReady());
        $this->assertFalse($preview->reconciliation->canApply());
        $this->assertTrue($preview->reconciliation->hasBlockingConflicts());
    }

    public function test_i_required_incompatible_version_blocks_preview(): void
    {
        // 'module' dependencies have no installed-version source at all
        // (Phase 3E-A) — a required constrained module dependency is
        // therefore always UNVERIFIABLE, never silently AVAILABLE. This
        // proves it blocks exactly like a genuinely incompatible version
        // would for a type that DOES track versions.
        $admin = $this->admin(modules: ['forms']);
        $this->installV1($admin);

        $this->mockDownloadSequence([$this->payload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => []]], 200, '2.0.0', [
            ['type' => 'module', 'identifier' => 'forms', 'is_required' => true, 'version_constraint' => '>=2.0', 'metadata' => []],
        ])]);

        $preview = app(ThemeUpgradePreviewService::class)->preview(1);

        $this->assertTrue($preview->isReady());
        $this->assertFalse($preview->reconciliation->canApply());
    }

    public function test_j_optional_missing_dependency_is_warning_only(): void
    {
        $admin = $this->admin(modules: ['blog']);
        $this->installV1($admin);

        $this->mockDownloadSequence([$this->payload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => []]], 200, '2.0.0', [
            ['type' => 'module', 'identifier' => 'forms', 'is_required' => false, 'version_constraint' => null, 'metadata' => []],
        ])]);

        $preview = app(ThemeUpgradePreviewService::class)->preview(1);

        $this->assertTrue($preview->isReady());
        $this->assertTrue($preview->reconciliation->canApply(), 'an optional unavailable dependency must never block');
        $this->assertSame([], $preview->reconciliation->conflicts);
        $this->assertNotEmpty($preview->reconciliation->warnings);
    }

    // ── K/L. apply recomputes server-side; client cannot dictate the result ─

    public function test_kl_apply_ignores_client_submitted_extra_fields_and_recomputes(): void
    {
        $admin = $this->admin();
        $this->installV1($admin);

        $this->mockDownloadSequence([$this->payload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => [$this->block('b1', 'text', ['props' => ['title' => 'A']])]]], 200, '2.0.0')]);
        $preview = $this->actingAs($admin)->get(route('admin.theme-marketplace.preview-upgrade', 1));
        $r = $preview->viewData('preview')->reconciliation;

        $this->mockDownloadSequence([$this->payload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => [$this->block('b1', 'text', ['props' => ['title' => 'A']])]]], 200, '2.0.0')]);

        $this->actingAs($admin)->post(route('admin.theme-marketplace.apply-upgrade', 1), [
            'release_id' => 200,
            'fingerprint' => $r->fingerprint,
            // Attempted client-supplied authority — must be completely ignored.
            'reconciled_theme' => ['pages' => ['home' => ['blocks' => [$this->block('malicious', 'text')]]]],
            'can_apply' => false,
            'conflicts' => [],
        ])->assertSessionHas('success');

        $applied = ThemeInstanceRevision::orderByDesc('revision_number')->first();
        $this->assertNotContains('malicious', array_column($applied->theme_data['pages']['home']['blocks'], 'id'));
        $this->assertSame('A', $applied->theme_data['pages']['home']['blocks'][0]['props']['title']);
    }

    // ── M. matching fingerprint applies ─────────────────────────────────────

    public function test_m_matching_fingerprint_applies(): void
    {
        $admin = $this->admin();
        $this->installV1($admin);

        $this->mockDownloadSequence([$this->payload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => [$this->block('b1', 'text', ['props' => ['title' => 'A']])]]], 200, '2.0.0')]);
        $preview = $this->actingAs($admin)->get(route('admin.theme-marketplace.preview-upgrade', 1));
        $r = $preview->viewData('preview')->reconciliation;

        $this->mockDownloadSequence([$this->payload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => [$this->block('b1', 'text', ['props' => ['title' => 'A']])]]], 200, '2.0.0')]);

        $this->actingAs($admin)->post(route('admin.theme-marketplace.apply-upgrade', 1), [
            'release_id' => 200,
            'fingerprint' => $r->fingerprint,
        ])->assertSessionHas('success');
    }

    // ── N. LOCAL edit after preview → stale abort ──────────────────────────

    public function test_n_local_edit_after_preview_causes_stale_abort(): void
    {
        $admin = $this->admin();
        $this->installV1($admin);

        $this->mockDownloadSequence([$this->payload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => [$this->block('b1', 'text', ['props' => ['title' => 'A']])]]], 200, '2.0.0')]);
        $preview = $this->actingAs($admin)->get(route('admin.theme-marketplace.preview-upgrade', 1));
        $r = $preview->viewData('preview')->reconciliation;

        // Customer publishes an edit AFTER preview but BEFORE apply.
        Setting::set('canvas_draft_data', json_encode(['pages' => ['home' => ['blocks' => [$this->block('b1', 'text', ['props' => ['title' => 'Changed after preview']])]]]]), 'theme');
        $this->actingAs($admin)->postJson(route('admin.theme-design.canvas-publish'));
        $revisionCountBefore = ThemeInstanceRevision::count();

        $this->mockDownloadSequence([$this->payload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => [$this->block('b1', 'text', ['props' => ['title' => 'A']])]]], 200, '2.0.0')]);

        $this->actingAs($admin)->post(route('admin.theme-marketplace.apply-upgrade', 1), [
            'release_id' => 200,
            'fingerprint' => $r->fingerprint,
        ])->assertSessionHas('error');

        $this->assertSame($revisionCountBefore, ThemeInstanceRevision::count(), 'a stale apply must never write a revision');
    }

    // ── O. target release change/mismatch → abort ──────────────────────────

    public function test_o_target_release_mismatch_aborts(): void
    {
        $admin = $this->admin();
        $this->installV1($admin);

        $this->mockDownloadSequence([$this->payload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => [$this->block('b1', 'text', ['props' => ['title' => 'A']])]]], 200, '2.0.0')]);
        $preview = $this->actingAs($admin)->get(route('admin.theme-marketplace.preview-upgrade', 1));
        $r = $preview->viewData('preview')->reconciliation;
        $revisionCountBefore = ThemeInstanceRevision::count();

        // A NEWER release (300) is now current on the platform.
        $this->mockDownloadSequence([$this->payload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => [$this->block('b1', 'text', ['props' => ['title' => 'A']])]]], 300, '3.0.0')]);

        $this->actingAs($admin)->post(route('admin.theme-marketplace.apply-upgrade', 1), [
            'release_id' => 200, // the OLD, previewed target — no longer current.
            'fingerprint' => $r->fingerprint,
        ])->assertSessionHas('error');

        $this->assertSame($revisionCountBefore, ThemeInstanceRevision::count());
    }

    // ── P. dependency becomes unavailable after preview → abort (TOCTOU) ───

    public function test_p_dependency_becomes_unavailable_after_preview_aborts(): void
    {
        $admin = $this->admin(modules: ['blog', 'forms']);
        $this->installV1($admin);

        $this->mockDownloadSequence([$this->payload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => []]], 200, '2.0.0', [
            ['type' => 'module', 'identifier' => 'forms', 'is_required' => true, 'version_constraint' => null, 'metadata' => []],
        ])]);
        $preview = $this->actingAs($admin)->get(route('admin.theme-marketplace.preview-upgrade', 1));
        $r = $preview->viewData('preview')->reconciliation;
        $this->assertTrue($r->canApply());
        $revisionCountBefore = ThemeInstanceRevision::count();

        // The forms module license is revoked between preview and apply.
        License::query()->update(['modules' => ['blog']]);

        $this->mockDownloadSequence([$this->payload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => []]], 200, '2.0.0', [
            ['type' => 'module', 'identifier' => 'forms', 'is_required' => true, 'version_constraint' => null, 'metadata' => []],
        ])]);

        $this->actingAs($admin)->post(route('admin.theme-marketplace.apply-upgrade', 1), [
            'release_id' => 200,
            'fingerprint' => $r->fingerprint,
        ])->assertSessionHas('error');

        $this->assertSame($revisionCountBefore, ThemeInstanceRevision::count());
    }

    // ── Q/R/S/T. successful apply: exactly one revision, pointer, type, provenance ─

    public function test_qrst_successful_apply_creates_one_correctly_typed_provenanced_revision(): void
    {
        $admin = $this->admin();
        $this->installV1($admin);
        $countBefore = ThemeInstanceRevision::count();
        $pointerBefore = (int) Setting::get('active_theme_revision_id');

        $this->mockDownloadSequence([$this->payload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => [$this->block('b1', 'text', ['props' => ['title' => 'A']])]]], 200, '2.0.0')]);
        $preview = $this->actingAs($admin)->get(route('admin.theme-marketplace.preview-upgrade', 1));
        $r = $preview->viewData('preview')->reconciliation;

        $this->mockDownloadSequence([$this->payload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => [$this->block('b1', 'text', ['props' => ['title' => 'A']])]]], 200, '2.0.0')]);
        $this->actingAs($admin)->post(route('admin.theme-marketplace.apply-upgrade', 1), [
            'release_id' => 200,
            'fingerprint' => $r->fingerprint,
        ])->assertSessionHas('success');

        $this->assertSame($countBefore + 1, ThemeInstanceRevision::count(), 'Q: exactly one new revision');

        $newPointer = (int) Setting::get('active_theme_revision_id');
        $this->assertNotSame($pointerBefore, $newPointer, 'R: pointer changed exactly once');

        $revision = ThemeInstanceRevision::find($newPointer);
        $this->assertSame(ThemeInstanceRevision::TYPE_UPGRADE_RECONCILED, $revision->type, 'S');
        $this->assertSame(200, $revision->source_release_id, 'T');
        $this->assertSame('2.0.0', $revision->source_theme_version, 'T');
    }

    // ── U. next BASE resolves to REMOTE release ─────────────────────────────

    public function test_u_next_base_resolves_to_remote_release(): void
    {
        $admin = $this->admin();
        $this->installV1($admin);

        $this->mockDownloadSequence([$this->payload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => [$this->block('b1', 'text', ['props' => ['title' => 'A']])]]], 200, '2.0.0')]);
        $preview = $this->actingAs($admin)->get(route('admin.theme-marketplace.preview-upgrade', 1));
        $r = $preview->viewData('preview')->reconciliation;

        $this->mockDownloadSequence([$this->payload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => [$this->block('b1', 'text', ['props' => ['title' => 'A']])]]], 200, '2.0.0')]);
        $this->actingAs($admin)->post(route('admin.theme-marketplace.apply-upgrade', 1), ['release_id' => 200, 'fingerprint' => $r->fingerprint]);

        $newBase = app(ThemeInstanceBaseRelease::class)->resolveForCurrentTheme();
        $this->assertTrue($newBase->isResolved());
        $this->assertSame(200, $newBase->sourceReleaseId, 'the just-applied REMOTE release is the BASE for the next upgrade');
    }

    // ── V. reconciliation failure (invalid REMOTE snapshot) → zero writes ──

    public function test_v_reconciliation_failure_writes_nothing(): void
    {
        $admin = $this->admin();
        $this->installV1($admin);
        $countBefore = ThemeInstanceRevision::count();

        // REMOTE snapshot with a duplicate block id — structurally invalid input.
        $badPayload = $this->payload('clinic-premium', 'کلینیک پریمیوم', 1, [
            'home' => ['blocks' => [$this->block('dup', 'text')]],
            'about' => ['blocks' => [$this->block('dup', 'heading')]],
        ], 200, '2.0.0');

        $this->mockDownloadSequence([$badPayload]);
        $preview = $this->actingAs($admin)->get(route('admin.theme-marketplace.preview-upgrade', 1));
        $preview->assertOk(); // the controller must not 500 — it should surface the failure gracefully.
        $this->assertSame(
            \App\Services\ThemeCanvas\ThemeUpgradePreviewResult::STATUS_RECONCILIATION_FAILED,
            $preview->viewData('preview')->status
        );

        $this->assertSame($countBefore, ThemeInstanceRevision::count());
    }

    // ── W. dependency blocker → zero writes ─────────────────────────────────

    public function test_w_dependency_blocker_apply_attempt_writes_nothing(): void
    {
        $admin = $this->admin(modules: ['blog']);
        $this->installV1($admin);
        $countBefore = ThemeInstanceRevision::count();

        $deps = [['type' => 'module', 'identifier' => 'forms', 'is_required' => true, 'version_constraint' => null, 'metadata' => []]];
        $this->mockDownloadSequence([$this->payload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => []]], 200, '2.0.0', $deps)]);
        $preview = $this->actingAs($admin)->get(route('admin.theme-marketplace.preview-upgrade', 1));
        $r = $preview->viewData('preview')->reconciliation;

        $this->mockDownloadSequence([$this->payload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => []]], 200, '2.0.0', $deps)]);
        $this->actingAs($admin)->post(route('admin.theme-marketplace.apply-upgrade', 1), [
            'release_id' => 200,
            'fingerprint' => $r->fingerprint,
        ])->assertSessionHas('error');

        $this->assertSame($countBefore, ThemeInstanceRevision::count());
    }

    // ── X. stale preview → zero writes (covered structurally by N, re-asserted) ─

    public function test_x_stale_preview_writes_nothing(): void
    {
        $admin = $this->admin();
        $this->installV1($admin);
        $countBefore = ThemeInstanceRevision::count();

        $this->mockDownloadSequence([$this->payload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => []]], 200, '2.0.0')]);
        $this->actingAs($admin)->post(route('admin.theme-marketplace.apply-upgrade', 1), [
            'release_id' => 200,
            'fingerprint' => 'forged-fingerprint-value',
        ])->assertSessionHas('error');

        $this->assertSame($countBefore, ThemeInstanceRevision::count());
    }

    // ── Z/AA. duplicate/concurrent apply → no duplicate revision ───────────

    public function test_zaa_duplicate_apply_does_not_create_a_second_revision(): void
    {
        $admin = $this->admin();
        $this->installV1($admin);
        $activeRevisionId = (int) Setting::get('active_theme_revision_id');

        $revisions = app(ThemeInstanceRevisionService::class);
        $themeData = ['pages' => ['home' => ['blocks' => []]]];
        $context = [
            'theme_identifier' => 'clinic-premium', 'theme_name' => 'کلینیک پریمیوم', 'active_theme_id' => 1,
            'source_theme_id' => 1, 'source_theme_version' => '2.0.0', 'source_release_id' => 200,
            'metadata' => [],
        ];

        $first = $revisions->recordUpgradeIfActiveRevisionUnchanged($activeRevisionId, $themeData, $context);
        $this->assertNotNull($first);

        // A second call with the SAME (now stale) expected id — simulates a
        // duplicate/concurrent Apply request racing the first.
        $second = $revisions->recordUpgradeIfActiveRevisionUnchanged($activeRevisionId, $themeData, $context);
        $this->assertNull($second, 'a second apply against the same now-superseded expectation must never write a duplicate revision');

        $this->assertSame(2, ThemeInstanceRevision::count()); // install + the one successful upgrade only.
    }

    // ── AB/AC. rollback after upgrade ────────────────────────────────────────

    public function test_ab_ac_rollback_after_upgrade_restores_pre_upgrade_state_and_base(): void
    {
        $admin = $this->admin();
        $this->installV1($admin);
        $preUpgradeRevisionId = (int) Setting::get('active_theme_revision_id');

        $this->mockDownloadSequence([$this->payload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => [$this->block('b1', 'text', ['props' => ['title' => 'A']])]]], 200, '2.0.0')]);
        $preview = $this->actingAs($admin)->get(route('admin.theme-marketplace.preview-upgrade', 1));
        $r = $preview->viewData('preview')->reconciliation;

        $this->mockDownloadSequence([$this->payload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => [$this->block('b1', 'text', ['props' => ['title' => 'A']])]]], 200, '2.0.0')]);
        $this->actingAs($admin)->post(route('admin.theme-marketplace.apply-upgrade', 1), ['release_id' => 200, 'fingerprint' => $r->fingerprint]);

        $countAfterUpgrade = ThemeInstanceRevision::count();

        $this->actingAs($admin)
            ->postJson(route('admin.theme-design.canvas-instance-revisions-rollback', $preUpgradeRevisionId))
            ->assertOk();

        $this->assertSame($countAfterUpgrade + 1, ThemeInstanceRevision::count(), 'AB: rollback creates N+1, never rewinds');

        $restoredBase = app(ThemeInstanceBaseRelease::class)->resolveForCurrentTheme();
        $this->assertTrue($restoredBase->isResolved());
        $this->assertSame(100, $restoredBase->sourceReleaseId, 'AC: BASE resolves to the historical (v1) release, not the upgraded one');
    }

    // ── AD. NON_RECONCILABLE does not guess BASE ────────────────────────────

    public function test_ad_non_reconcilable_never_guesses(): void
    {
        $admin = $this->admin();
        // A from-scratch theme with no marketplace install/real provenance.
        Setting::set('canvas_draft_data', json_encode(['pages' => ['home' => ['blocks' => []]]]), 'theme');
        $this->actingAs($admin)->postJson(route('admin.theme-design.canvas-publish'));

        $response = $this->actingAs($admin)->get(route('admin.theme-marketplace.preview-upgrade', 1));
        $response->assertOk();
        $this->assertSame(ThemeUpgradePreviewResult::STATUS_NON_RECONCILABLE, $response->viewData('preview')->status);
    }

    // ── AF. preview serialization contains no secrets ──────────────────────

    public function test_af_preview_serialization_has_no_secrets(): void
    {
        $admin = $this->admin();
        $this->installV1($admin);

        $this->mockDownloadSequence([$this->payload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => []]], 200, '2.0.0')]);
        $preview = app(ThemeUpgradePreviewService::class)->preview(1);

        $encoded = json_encode($preview->toArray());
        $this->assertIsString($encoded);
        foreach (['license_key', 'password', 'secret', 'token', 'api_key'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, strtolower($encoded));
        }
    }

    // ── AG. existing install flow still works ───────────────────────────────

    public function test_ag_existing_fresh_install_flow_still_works(): void
    {
        $admin = $this->admin();
        $this->mockDownloadSequence([$this->payload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => []]], 100, '1.0.0')]);

        $this->actingAs($admin)
            ->post(route('admin.theme-marketplace.install', 1))
            ->assertSessionHas('success');

        $this->assertSame(ThemeInstanceRevision::TYPE_INSTALL, ThemeInstanceRevision::sole()->type);
    }
}

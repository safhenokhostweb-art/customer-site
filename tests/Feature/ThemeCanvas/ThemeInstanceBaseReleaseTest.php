<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\License;
use App\Models\Setting;
use App\Models\ThemeInstanceRevision;
use App\Models\User;
use App\Services\PlatformApiService;
use App\Services\ThemeCanvas\ThemeBaseReleaseResolution;
use App\Services\ThemeCanvas\ThemeInstanceBaseRelease;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Phase 3D-B — BASE release resolution and provenance propagation, the
 * foundation the future BASE/LOCAL/REMOTE upgrade reconciler (Phase 3D-C/D)
 * will rely on. ThemeInstanceBaseRelease never infers/guesses: RESOLVED
 * only when a real source_release_id is provably reachable through the
 * immutable revision ledger; every other case is NON_RECONCILABLE.
 */
class ThemeInstanceBaseReleaseTest extends TestCase
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
            'license_key' => 'BASE-RELEASE-' . uniqid(),
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

    private function themePayload(string $slug, string $name, int $themeId, array $pages = [], array $overrides = []): array
    {
        return array_merge([
            'success' => true,
            'theme_id' => $themeId,
            'theme_name' => $name,
            'theme_slug' => $slug,
            'header_blocks' => [],
            'footer_blocks' => [],
            'pages' => $pages,
            'global_styles' => [],
            'settings' => [],
        ], $overrides);
    }

    private function resolver(): ThemeInstanceBaseRelease
    {
        return app(ThemeInstanceBaseRelease::class);
    }

    // ── A. install revision resolves exact BASE release ──────────────────

    public function test_install_resolves_exact_base_release(): void
    {
        $admin = $this->admin();
        $this->mockPlatformDownloads([
            $this->themePayload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => ['v1']]], ['release_version' => '1.0.0', 'release_id' => 100]),
        ]);
        $this->actingAs($admin)->post(route('admin.theme-marketplace.install', 1));

        $resolution = $this->resolver()->resolveForCurrentTheme();

        $this->assertTrue($resolution->isResolved());
        $this->assertSame('clinic-premium', $resolution->themeIdentifier);
        $this->assertSame('1.0.0', $resolution->sourceThemeVersion);
        $this->assertSame(100, $resolution->sourceReleaseId);
    }

    // ── B. publish after install preserves/resolves same BASE ────────────

    public function test_publish_after_install_resolves_same_base(): void
    {
        $admin = $this->admin();
        $this->mockPlatformDownloads([
            $this->themePayload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => ['v1']]], ['release_version' => '1.0.0', 'release_id' => 100]),
        ]);
        $this->actingAs($admin)->post(route('admin.theme-marketplace.install', 1));

        Setting::set('canvas_draft_data', json_encode(['pages' => ['home' => ['blocks' => ['v2']]]]), 'theme');
        $this->actingAs($admin)->postJson(route('admin.theme-design.canvas-publish'))->assertOk();

        $resolution = $this->resolver()->resolveForCurrentTheme();

        $this->assertTrue($resolution->isResolved());
        $this->assertSame(100, $resolution->sourceReleaseId);
        $this->assertSame('1.0.0', $resolution->sourceThemeVersion);

        // The publish revision itself now carries the provenance directly
        // (propagated forward), not just resolvable via a backward walk.
        $publishRevision = ThemeInstanceRevision::where('theme_identifier', 'clinic-premium')
            ->where('type', ThemeInstanceRevision::TYPE_PUBLISH)->sole();
        $this->assertSame(100, $publishRevision->source_release_id);
    }

    // ── C. repeated publishes preserve BASE ───────────────────────────────

    public function test_repeated_publishes_preserve_base(): void
    {
        $admin = $this->admin();
        $this->mockPlatformDownloads([
            $this->themePayload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => ['v1']]], ['release_version' => '1.0.0', 'release_id' => 100]),
        ]);
        $this->actingAs($admin)->post(route('admin.theme-marketplace.install', 1));

        foreach (['v2', 'v3', 'v4'] as $v) {
            Setting::set('canvas_draft_data', json_encode(['pages' => ['home' => ['blocks' => [$v]]]]), 'theme');
            $this->actingAs($admin)->postJson(route('admin.theme-design.canvas-publish'))->assertOk();
        }

        $resolution = $this->resolver()->resolveForCurrentTheme();
        $this->assertTrue($resolution->isResolved());
        $this->assertSame(100, $resolution->sourceReleaseId);
    }

    // ── D. upgrade to newer release establishes newer BASE ────────────────

    public function test_upgrade_to_newer_release_establishes_newer_base(): void
    {
        $admin = $this->admin();
        $this->mockPlatformDownloads([
            $this->themePayload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => ['v1']]], ['release_version' => '1.0.0', 'release_id' => 100]),
            $this->themePayload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => ['v2']]], ['release_version' => '2.0.0', 'release_id' => 200]),
        ]);
        $this->actingAs($admin)->post(route('admin.theme-marketplace.install', 1));
        $this->actingAs($admin)->post(route('admin.theme-marketplace.install', 1), ['confirm_overwrite' => '1']);

        $resolution = $this->resolver()->resolveForCurrentTheme();

        $this->assertTrue($resolution->isResolved());
        $this->assertSame(200, $resolution->sourceReleaseId);
        $this->assertSame('2.0.0', $resolution->sourceThemeVersion);
    }

    // ── E. publish after upgrade resolves newer BASE ──────────────────────

    public function test_publish_after_upgrade_resolves_newer_base(): void
    {
        $admin = $this->admin();
        $this->mockPlatformDownloads([
            $this->themePayload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => ['v1']]], ['release_version' => '1.0.0', 'release_id' => 100]),
            $this->themePayload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => ['v2']]], ['release_version' => '2.0.0', 'release_id' => 200]),
        ]);
        $this->actingAs($admin)->post(route('admin.theme-marketplace.install', 1));
        $this->actingAs($admin)->post(route('admin.theme-marketplace.install', 1), ['confirm_overwrite' => '1']);

        Setting::set('canvas_draft_data', json_encode(['pages' => ['home' => ['blocks' => ['v2-customized']]]]), 'theme');
        $this->actingAs($admin)->postJson(route('admin.theme-design.canvas-publish'))->assertOk();

        $resolution = $this->resolver()->resolveForCurrentTheme();
        $this->assertTrue($resolution->isResolved());
        $this->assertSame(200, $resolution->sourceReleaseId);
    }

    // ── F/G. rollback resolves the TARGET's release, never the newest ────

    public function test_rollback_to_old_release_derived_revision_resolves_old_base_not_newest(): void
    {
        $admin = $this->admin();
        $this->mockPlatformDownloads([
            $this->themePayload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => ['v1']]], ['release_version' => '1.0.0', 'release_id' => 100]),
            $this->themePayload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => ['v2']]], ['release_version' => '2.0.0', 'release_id' => 200]),
        ]);
        $this->actingAs($admin)->post(route('admin.theme-marketplace.install', 1));
        $firstRevisionId = ThemeInstanceRevision::where('theme_identifier', 'clinic-premium')->sole()->id;

        $this->actingAs($admin)->post(route('admin.theme-marketplace.install', 1), ['confirm_overwrite' => '1']);
        // Active BASE is now release 200.
        $this->assertSame(200, $this->resolver()->resolveForCurrentTheme()->sourceReleaseId);

        // Roll back to the v1.0.0-derived revision.
        $this->actingAs($admin)
            ->postJson(route('admin.theme-design.canvas-instance-revisions-rollback', $firstRevisionId))
            ->assertOk();

        $resolution = $this->resolver()->resolveForCurrentTheme();
        $this->assertTrue($resolution->isResolved());
        $this->assertSame(100, $resolution->sourceReleaseId, 'rollback must resolve the TARGET revision\'s release, not whatever was active before the rollback');
        $this->assertSame('1.0.0', $resolution->sourceThemeVersion);

        // The new rollback revision itself carries the provenance directly.
        $rollbackRevision = ThemeInstanceRevision::where('theme_identifier', 'clinic-premium')
            ->where('type', ThemeInstanceRevision::TYPE_ROLLBACK_SNAPSHOT)->sole();
        $this->assertSame(100, $rollbackRevision->source_release_id);
    }

    // ── H. custom/import lineage without provable release is NON_RECONCILABLE ─

    public function test_from_scratch_custom_lineage_is_non_reconcilable(): void
    {
        $admin = $this->admin();
        Setting::set('canvas_draft_data', json_encode(['pages' => ['home' => ['blocks' => ['from-scratch']]]]), 'theme');
        $this->actingAs($admin)->postJson(route('admin.theme-design.canvas-publish'))->assertOk();

        $resolution = $this->resolver()->resolveForCurrentTheme();
        $this->assertFalse($resolution->isResolved());
        $this->assertSame(ThemeBaseReleaseResolution::STATUS_NON_RECONCILABLE, $resolution->status);
    }

    public function test_no_active_revision_at_all_is_non_reconcilable(): void
    {
        $resolution = $this->resolver()->resolveForCurrentTheme();
        $this->assertFalse($resolution->isResolved());
    }

    // ── Import provenance: verified round-trip vs forged claim ───────────

    private function importFile(array $data): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('theme.json', json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    public function test_import_without_any_provenance_claim_is_non_reconcilable(): void
    {
        $admin = $this->admin();
        $payload = [
            'pages' => ['home' => ['blocks' => ['imported']]],
            '_export_meta' => ['theme_slug' => 'imported-theme'],
        ];
        $this->actingAs($admin)
            ->post(route('admin.theme-design.canvas-import'), ['theme_file' => $this->importFile($payload)]);

        $revision = ThemeInstanceRevision::where('theme_identifier', 'imported-theme')->sole();
        $this->assertNull($revision->source_release_id);
        $this->assertFalse($this->resolver()->resolveForCurrentTheme()->isResolved());
    }

    // ── I. verified export/import round-trip preserves provenance ────────

    public function test_verified_export_import_round_trip_preserves_provenance(): void
    {
        $admin = $this->admin();
        $this->mockPlatformDownloads([
            $this->themePayload('clinic-premium', 'کلینیک پریمیوم', 1, ['home' => ['blocks' => ['v1']]], ['release_version' => '1.0.0', 'release_id' => 100]),
        ]);
        $this->actingAs($admin)->post(route('admin.theme-marketplace.install', 1));

        // Export the genuinely-installed theme — its _export_meta must now
        // carry the real, ledger-proven provenance (Phase 3D-B canvasExport()).
        $exportResponse = $this->actingAs($admin)->get(route('admin.theme-design.canvas-export'));
        $exportResponse->assertOk();
        $exported = json_decode($exportResponse->getContent(), true);
        $this->assertSame(100, $exported['_export_meta']['source_release_id']);
        $this->assertSame('1.0.0', $exported['_export_meta']['source_theme_version']);

        // Deactivate (or simulate a fresh site) and re-import the exact
        // exported file back into this SAME installation.
        $this->actingAs($admin)->post(route('admin.theme-design.canvas-deactivate'));

        $this->actingAs($admin)
            ->post(route('admin.theme-design.canvas-import'), ['theme_file' => $this->importFile($exported)])
            ->assertSessionHas('success');

        $imported = ThemeInstanceRevision::where('theme_identifier', 'clinic-premium')
            ->orderByDesc('revision_number')->first();
        $this->assertSame(100, $imported->source_release_id, 'a round-trip re-import into the SAME installation must verify and carry the real provenance forward');
        $this->assertSame('1.0.0', $imported->source_theme_version);
        $this->assertTrue($this->resolver()->resolveForCurrentTheme()->isResolved());
    }

    // ── J. forged/unverifiable import provenance is rejected ─────────────

    public function test_forged_import_provenance_is_rejected_and_stays_non_reconcilable(): void
    {
        $admin = $this->admin();

        // This installation has NEVER recorded (theme_identifier=clinic-premium,
        // source_release_id=999) anywhere in its own ledger — the claim below
        // is exactly what a hand-edited or cross-site export file would look
        // like, and must not be trusted just because it's well-formed.
        $forged = [
            'pages' => ['home' => ['blocks' => ['forged']]],
            '_export_meta' => [
                'theme_slug' => 'clinic-premium',
                'source_release_id' => 999,
                'source_theme_version' => '9.9.9',
            ],
        ];

        $this->actingAs($admin)
            ->post(route('admin.theme-design.canvas-import'), ['theme_file' => $this->importFile($forged)])
            ->assertSessionHas('success');

        $revision = ThemeInstanceRevision::where('theme_identifier', 'clinic-premium')->sole();
        $this->assertNull($revision->source_release_id, 'an unverifiable claimed release id must never be trusted');
        $this->assertNull($revision->source_theme_version);
        $this->assertFalse($this->resolver()->resolveForCurrentTheme()->isResolved());
    }

    public function test_forged_import_claiming_a_release_id_that_belongs_to_a_different_lineage_is_rejected(): void
    {
        $admin = $this->admin();
        // A REAL release id exists locally, but under a DIFFERENT theme
        // lineage — reusing that real number for an unrelated slug must
        // still fail verification (the check is scoped to theme_identifier
        // + source_release_id together, not the id alone).
        $this->mockPlatformDownloads([
            $this->themePayload('theme-a', 'قالب الف', 1, ['home' => ['blocks' => ['a1']]], ['release_version' => '1.0.0', 'release_id' => 555]),
        ]);
        $this->actingAs($admin)->post(route('admin.theme-marketplace.install', 1));

        $forged = [
            'pages' => ['home' => ['blocks' => ['forged']]],
            '_export_meta' => [
                'theme_slug' => 'a-completely-different-theme',
                'source_release_id' => 555,
                'source_theme_version' => '1.0.0',
            ],
        ];

        $this->actingAs($admin)
            ->post(route('admin.theme-design.canvas-import'), ['theme_file' => $this->importFile($forged)]);

        $revision = ThemeInstanceRevision::where('theme_identifier', 'a-completely-different-theme')->sole();
        $this->assertNull($revision->source_release_id);
    }
}

<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\License;
use App\Models\ThemeInstanceRevision;
use App\Models\User;
use App\Services\PlatformApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 1h — Customer-Site ThemeRelease Consumer Migration.
 *
 * Two concerns: (1) the generic structured-dependency partial renders safely
 * for every dependency shape, including types nobody has invented yet, and
 * never presents "unresolved" as "no dependencies"; (2) ThemeRelease
 * identity (semver + release id) returned by the platform's /download
 * response is propagated into theme_instance_revisions.source_theme_version/
 * source_release_id — the confirmed gap this phase closes — without ever
 * fabricating a value when the platform doesn't supply one.
 */
class ThemeReleaseConsumerMigrationTest extends TestCase
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
            'license_key' => 'REL-MIGRATE-' . uniqid(),
            'domain' => request()->getHost(),
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => ['shop', 'blog'],
        ]);

        return User::factory()->create(['is_admin' => true]);
    }

    private function mockDownload(array $payload): void
    {
        $this->mockDownloadSequence([$payload]);
    }

    /**
     * Mockery's handling of repeated shouldReceive setup for the same
     * unconstrained method is unreliable across separate $this->mock()
     * calls (see ThemeInstanceRevisionTest's mockPlatformDownloads()) — every
     * download a test needs must be queued through one mock instance.
     */
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

    private function basePayload(array $overrides = []): array
    {
        return array_merge([
            'success' => true,
            'theme_id' => 1,
            'theme_name' => 'کلینیک پریمیوم',
            'theme_slug' => 'clinic-premium',
            'header_blocks' => [],
            'footer_blocks' => [],
            'pages' => ['home' => ['blocks' => []]],
            'global_styles' => [],
            'settings' => [],
        ], $overrides);
    }

    // ── dependency partial rendering ─────────────────────────────────────

    private function renderDependencies(array $theme): string
    {
        return view('admin.theme-marketplace.partials.dependencies', ['theme' => $theme])->render();
    }

    public function test_a_structured_module_dependency_renders(): void
    {
        $html = $this->renderDependencies([
            'dependency_status' => 'resolved',
            'dependencies' => [
                ['type' => 'module', 'identifier' => 'forms', 'is_required' => false, 'version_constraint' => null, 'metadata' => ['description' => 'فرم تماس']],
            ],
        ]);

        $this->assertStringContainsString('forms', $html);
        $this->assertStringContainsString('ماژول', $html);
        $this->assertStringContainsString('فرم تماس', $html);
        $this->assertStringContainsString('اختیاری', $html);
    }

    public function test_a_motion_dependency_renders(): void
    {
        $html = $this->renderDependencies([
            'dependency_status' => 'resolved',
            'dependencies' => [
                ['type' => 'motion_asset', 'identifier' => 'medical-hero-intro', 'is_required' => true, 'version_constraint' => '>=1.0', 'metadata' => []],
            ],
        ]);

        $this->assertStringContainsString('medical-hero-intro', $html);
        $this->assertStringContainsString('دارایی موشن', $html);
        // Blade escapes output, so ">=1.0" appears HTML-entity-encoded.
        $this->assertStringContainsString(e('>=1.0'), $html);
        $this->assertStringContainsString('الزامی', $html);
    }

    public function test_an_unknown_future_dependency_type_renders_safely(): void
    {
        $html = $this->renderDependencies([
            'dependency_status' => 'resolved',
            'dependencies' => [
                ['type' => 'quantum_widget_pack', 'identifier' => 'not-invented-yet', 'is_required' => false, 'version_constraint' => null, 'metadata' => []],
            ],
        ]);

        // No hardcoded branch exists for this type — it must still render,
        // falling back to the raw type string rather than throwing or
        // silently dropping the row.
        $this->assertStringContainsString('not-invented-yet', $html);
        $this->assertStringContainsString('quantum_widget_pack', $html);
    }

    public function test_unresolved_dependencies_are_never_shown_as_dependency_free(): void
    {
        $html = $this->renderDependencies(['dependency_status' => 'unresolved', 'dependencies' => []]);

        $this->assertStringNotContainsString('نیازمندی خاصی ندارد', $html);
        $this->assertStringContainsString('تأیید نشده', $html);
    }

    public function test_a_genuinely_resolved_empty_dependency_list_is_shown_as_dependency_free(): void
    {
        $html = $this->renderDependencies(['dependency_status' => 'resolved', 'dependencies' => []]);

        $this->assertStringContainsString('نیازمندی خاصی ندارد', $html);
    }

    // ── release identity propagation into theme_instance_revisions ──────

    public function test_source_theme_version_and_release_id_are_recorded_on_install(): void
    {
        $admin = $this->admin();
        $this->mockDownload($this->basePayload(['release_version' => '1.4.0', 'release_id' => 42]));

        $this->actingAs($admin)
            ->post(route('admin.theme-marketplace.install', 1))
            ->assertSessionHas('success');

        $revision = ThemeInstanceRevision::where('theme_identifier', 'clinic-premium')->sole();
        $this->assertSame('1.4.0', $revision->source_theme_version);
        $this->assertSame(42, $revision->source_release_id);
    }

    public function test_source_theme_version_and_release_id_are_recorded_on_update(): void
    {
        $admin = $this->admin();
        $this->mockDownloadSequence([
            $this->basePayload(['release_version' => '1.0.0', 'release_id' => 10]),
            $this->basePayload(['release_version' => '1.1.0', 'release_id' => 11]),
        ]);
        $this->actingAs($admin)->post(route('admin.theme-marketplace.install', 1));

        $this->actingAs($admin)
            ->post(route('admin.theme-marketplace.install', 1), ['confirm_overwrite' => '1'])
            ->assertSessionHas('success');

        $revisions = ThemeInstanceRevision::where('theme_identifier', 'clinic-premium')->orderBy('revision_number')->get();
        $this->assertCount(2, $revisions);
        $this->assertSame(ThemeInstanceRevision::TYPE_UPDATE, $revisions[1]->type);
        $this->assertSame('1.0.0', $revisions[0]->source_theme_version);
        $this->assertSame('1.1.0', $revisions[1]->source_theme_version);
        $this->assertSame(11, $revisions[1]->source_release_id);
    }

    public function test_a_missing_release_version_in_the_download_response_stays_null_not_fabricated(): void
    {
        $admin = $this->admin();
        // No release_version/release_id keys at all — the theme has never
        // been through the real publish flow yet, exactly the state every
        // pre-Phase-1d theme was in.
        $this->mockDownload($this->basePayload());

        $this->actingAs($admin)
            ->post(route('admin.theme-marketplace.install', 1))
            ->assertSessionHas('success');

        $revision = ThemeInstanceRevision::where('theme_identifier', 'clinic-premium')->sole();
        $this->assertNull($revision->source_theme_version);
        $this->assertNull($revision->source_release_id);
    }
}

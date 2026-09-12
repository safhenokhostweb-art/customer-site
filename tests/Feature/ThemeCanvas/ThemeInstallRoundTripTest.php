<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\License;
use App\Models\Page;
use App\Models\Setting;
use App\Models\ThemeInstanceRevision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Builder integrity final gate (2026-09-12) — ADR-052.
 *
 * Permanent regression for the real saas-platform -> customer-site Theme
 * install call graph: ThemeRelease -> ThemeCanvasController::installToSite()
 * -> HTTP POST /builder/api/theme/install -> PageBuilderController::installTheme()
 * -> ThemeInstanceRevisionService::record() -> Setting::active_canvas_theme_data
 * / active_theme_revision_id -> ThemeCanvasPageController public render.
 *
 * This hits the REAL route (through the real 'provision.secret' middleware),
 * the REAL controller, the REAL ThemeInstanceRevisionService, and a REAL
 * migrated database (RefreshDatabase) — no direct-DB shortcut and no
 * fake/mocked install adapter. It is the customer-site half of the
 * install contract; the saas-platform half (that installToSite() sends
 * exactly this payload to exactly this endpoint) is proven separately by
 * saas-platform/tests/Feature/Admin/ThemeCanvasInstallToSiteHttpTest.php.
 *
 * Before the fix, installToSite() wrote active_canvas_theme_data directly
 * via a raw cross-DB connection AND raw-inserted real is_protected Page
 * rows for every installed page slug — which collided with the very same
 * slugs the installed Canvas theme owned, and made ThemeInstanceRevisionService's
 * own CanvasReservedSlugs collision guard permanently reject any later
 * save/publish/backfill for that lineage. This test proves that no longer
 * happens: install goes through the same single write path as every other
 * way the active Canvas theme can change, so no Page row is ever created
 * for a Canvas-owned slug, and a normal post-install save/publish succeeds.
 */
class ThemeInstallRoundTripTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        config([
            'site.owner_site' => true,
            'services.customer_provision_secret' => 'install-round-trip-test-secret',
        ]);
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    private function admin(): User
    {
        License::create([
            'license_key' => 'INSTALL-RT-' . uniqid(),
            'domain' => request()->getHost(),
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => ['shop', 'blog'],
        ]);

        return User::factory()->create(['is_admin' => true]);
    }

    /** A realistic, multi-page install payload mirroring what installToSite() sends. */
    private function installPayload(array $overrides = []): array
    {
        return array_merge([
            'theme_id' => 'universal-multipurpose-qa',
            'theme_name' => 'قالب جامع چندمنظوره',
            'source_theme_id' => '42',
            'source_theme_version' => '1.0.0',
            'source_release_id' => 7,
            'header_blocks' => [
                ['type' => 'nav_header', 'props' => ['logo_text' => 'برند شما', 'nav_links' => []]],
            ],
            'footer_blocks' => [
                ['type' => 'nav_footer', 'props' => ['logo_text' => 'برند شما', 'copyright' => '©']],
            ],
            'pages' => [
                'home' => ['title' => 'خانه', 'slug' => '', 'blocks' => [
                    ['type' => 'hero', 'props' => ['title' => 'خانه نصب‌شده']],
                ]],
                'about' => ['title' => 'درباره ما', 'slug' => 'about', 'blocks' => [
                    ['type' => 'about_section', 'props' => ['title' => 'دربارهٔ ما']],
                ]],
                'contact' => ['title' => 'تماس با ما', 'slug' => 'contact', 'blocks' => [
                    ['type' => 'contact_info', 'props' => ['title' => 'تماس']],
                ]],
            ],
            'global_styles' => ['primary_color' => '#4f46e5'],
            'extension_assets' => [],
        ], $overrides);
    }

    private function callInstall(array $payload): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders(['X-Provision-Secret' => 'install-round-trip-test-secret'])
            ->postJson(route('builder.api.theme.install'), $payload);
    }

    // ── 1. Real install creates a canonical revision with real provenance ──

    public function test_real_install_creates_one_canonical_revision_with_provenance_and_activates_it(): void
    {
        $response = $this->callInstall($this->installPayload());

        $response->assertOk();
        $response->assertJson(['success' => true, 'pages_installed' => 3]);

        $revisions = ThemeInstanceRevision::where('theme_identifier', 'universal-multipurpose-qa')->get();
        $this->assertCount(1, $revisions, 'install must create exactly one canonical revision');

        $revision = $revisions->first();
        $this->assertSame(1, $revision->revision_number);
        $this->assertSame(ThemeInstanceRevision::TYPE_INSTALL, $revision->type);
        $this->assertSame(42, $revision->source_theme_id);
        $this->assertSame('1.0.0', $revision->source_theme_version);
        $this->assertSame(7, $revision->source_release_id);
        $this->assertCount(3, $revision->theme_data['pages']);

        // Active state consistent: the pointer really points at this revision.
        $this->assertSame($revision->id, (int) Setting::get('active_theme_revision_id'));
        $this->assertSame('universal-multipurpose-qa', Setting::get('active_canvas_theme_slug'));
        $this->assertSame('قالب جامع چندمنظوره', Setting::get('active_canvas_theme_name'));

        $activeData = json_decode(Setting::get('active_canvas_theme_data'), true);
        $this->assertCount(3, $activeData['pages']);
        $this->assertNotEmpty($activeData['header_blocks']);
        $this->assertNotEmpty($activeData['footer_blocks']);

        // No raw legacy Page rows were created for the installed slugs —
        // the single-authority fix means the install namespace never
        // shadows/collides with Page Builder's own table.
        $this->assertSame(0, Page::whereIn('slug', ['about', 'contact'])->count());
    }

    // ── 2. Public render immediately reflects the installed pages ──────────

    public function test_public_pages_render_correctly_immediately_after_install(): void
    {
        $this->callInstall($this->installPayload())->assertOk();

        $home = $this->get('/');
        $home->assertOk();
        $home->assertSee('خانه نصب‌شده');

        $about = $this->get('/page/about');
        $about->assertOk();
        $about->assertSee('دربارهٔ ما');
    }

    // ── 3. A normal post-install Canvas save/publish succeeds — no collision ─

    public function test_normal_save_publish_after_install_succeeds_without_page_namespace_collision(): void
    {
        $this->callInstall($this->installPayload())->assertOk();
        $admin = $this->admin();

        $activeData = json_decode(Setting::get('active_canvas_theme_data'), true);
        $activeData['pages']['about']['blocks'] = [
            ['type' => 'about_section', 'props' => ['title' => 'محتوای ویرایش‌شده پس از نصب']],
        ];
        Setting::set('canvas_draft_data', json_encode($activeData, JSON_UNESCAPED_UNICODE), 'theme');

        $publish = $this->actingAs($admin)->postJson(route('admin.theme-design.canvas-publish'));
        $publish->assertOk();
        $publish->assertJson(['success' => true]);

        $revisions = ThemeInstanceRevision::where('theme_identifier', 'universal-multipurpose-qa')
            ->orderBy('revision_number')->get();
        $this->assertCount(2, $revisions, 'publish after install must add a revision, not collide or fail');
        $this->assertSame(ThemeInstanceRevision::TYPE_PUBLISH, $revisions[1]->type);
        // Provenance is carried forward from the install revision, not lost.
        $this->assertSame('1.0.0', $revisions[1]->source_theme_version);
        $this->assertSame(7, $revisions[1]->source_release_id);

        // Public render now reflects the published change.
        $about = $this->get('/page/about');
        $about->assertOk();
        $about->assertSee('محتوای ویرایش‌شده پس از نصب');
    }

    // ── 4. The collision guard still fails CLOSED when a real Page conflicts ─

    public function test_install_is_still_rejected_when_a_page_row_already_owns_one_of_the_slugs(): void
    {
        Page::create([
            'title' => 'صفحه موجود از قبل',
            'slug' => 'about',
            'is_published' => true,
            'show_in_menu' => false,
            'builder_type' => Page::BUILDER_TYPE_BUILDER,
            'builder_content_v2' => ['blocks' => []],
            'order' => 1,
        ]);

        $response = $this->callInstall($this->installPayload());

        $response->assertStatus(409);
        $response->assertJson(['success' => false, 'error' => 'page_namespace_collision']);

        // Nothing was activated — a rejected install must not partially apply.
        $this->assertCount(0, ThemeInstanceRevision::where('theme_identifier', 'universal-multipurpose-qa')->get());
        $this->assertEmpty(Setting::get('active_canvas_theme_data'));
    }

    // ── 5. Wrong/missing provisioning secret is rejected, proving the guard is live ─

    public function test_install_endpoint_rejects_requests_without_the_correct_provision_secret(): void
    {
        $response = $this->postJson(route('builder.api.theme.install'), $this->installPayload());
        $response->assertStatus(403);

        $this->assertCount(0, ThemeInstanceRevision::where('theme_identifier', 'universal-multipurpose-qa')->get());
    }
}

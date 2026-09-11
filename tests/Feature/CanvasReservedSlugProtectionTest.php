<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 2026-09-11 systemic root-cause fix — ThemeCanvasPageController::show()
 * resolves an active canvas theme's own `pages[slug]` before it ever looks
 * at a Page row of the same slug (routes/web.php: "ThemeCanvasPageController
 * delegates to ... when no active canvas theme is installed"). Before this
 * fix, both Page-authoring surfaces (Page Builder's createPage/
 * updatePageSettings, and the traditional Admin Pages CRUD) let an admin
 * create or rename a Page into a canvas-owned slug with zero warning — the
 * page saved successfully but could never be publicly reached. Proven live
 * via tests/browser/save-to-public-render.spec.js; this is the fast PHP-level
 * companion covering both entry points.
 */
class CanvasReservedSlugProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        config(['site.owner_site' => true]);

        Setting::set('active_canvas_theme_data', json_encode([
            'pages' => [
                'home' => ['title' => 'Home', 'slug' => 'home', 'is_home' => true, 'blocks' => []],
                'ext-bridge' => ['title' => 'Extension Bridge', 'slug' => 'ext-bridge', 'is_home' => false, 'blocks' => []],
            ],
            'header_blocks' => [],
            'footer_blocks' => [],
            'global_styles' => [],
        ], JSON_UNESCAPED_UNICODE), 'theme');
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    protected function createAdmin(): User
    {
        $user = User::factory()->create();
        $user->update(['is_admin' => true]);
        return $user;
    }

    public function test_page_builder_create_page_rejects_a_canvas_owned_slug(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $response = $this->actingAs($this->createAdmin())->postJson(route('builder.api.pages.create'), [
            'title' => 'Collision Probe',
            'slug'  => 'ext-bridge',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertStringContainsString('ext-bridge', $response->json('error'));
        $this->assertDatabaseMissing('pages', ['slug' => 'ext-bridge']);
    }

    public function test_page_builder_update_page_settings_rejects_renaming_into_a_canvas_owned_slug(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $page = Page::create([
            'title' => 'Renamable Page', 'slug' => 'renamable-page', 'body' => '',
            'builder_content_v2' => ['blocks' => []], 'builder_type' => Page::BUILDER_TYPE_BUILDER,
            'is_published' => true, 'show_in_menu' => false,
        ]);

        $response = $this->actingAs($this->createAdmin())
            ->putJson(route('builder.api.page.settings', $page), ['slug' => 'ext-bridge']);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertSame('renamable-page', $page->fresh()->slug);
    }

    public function test_page_builder_create_page_still_succeeds_for_a_non_colliding_slug(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $response = $this->actingAs($this->createAdmin())->postJson(route('builder.api.pages.create'), [
            'title' => 'Safe Probe',
            'slug'  => 'safe-probe-page',
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('pages', ['slug' => 'safe-probe-page']);
    }

    public function test_admin_pages_store_rejects_a_canvas_owned_slug(): void
    {
        $response = $this->actingAs($this->createAdmin())->post(route('admin.pages.store'), [
            'title' => 'Collision Probe',
            'slug'  => 'ext-bridge',
        ]);

        $response->assertSessionHasErrors('slug');
        $this->assertDatabaseMissing('pages', ['slug' => 'ext-bridge']);
    }

    public function test_admin_pages_update_rejects_renaming_into_a_canvas_owned_slug(): void
    {
        $page = Page::create([
            'title' => 'Renamable Admin Page', 'slug' => 'renamable-admin-page', 'body' => '',
            'is_published' => true, 'show_in_menu' => false, 'order' => 1,
        ]);

        $response = $this->actingAs($this->createAdmin())->put(route('admin.pages.update', $page), [
            'title' => 'Renamable Admin Page',
            'slug'  => 'ext-bridge',
            'builder_header_override' => '__none__',
            'builder_footer_override' => '__none__',
        ]);

        $response->assertSessionHasErrors('slug');
        $this->assertSame('renamable-admin-page', $page->fresh()->slug);
    }

    public function test_admin_pages_update_allows_resubmitting_the_unchanged_slug(): void
    {
        // A page whose slug already happens to collide (created before this
        // fix existed, or via direct DB seeding) must still be editable for
        // its OTHER fields — only an actual slug change into reserved
        // territory is rejected.
        $page = Page::create([
            'title' => 'Grandfathered Page', 'slug' => 'ext-bridge', 'body' => '',
            'is_published' => true, 'show_in_menu' => false, 'order' => 1,
        ]);

        $response = $this->actingAs($this->createAdmin())->put(route('admin.pages.update', $page), [
            'title' => 'Grandfathered Page Renamed Title',
            'slug'  => 'ext-bridge',
            'builder_header_override' => '__none__',
            'builder_footer_override' => '__none__',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertSame('Grandfathered Page Renamed Title', $page->fresh()->title);
    }

    // ── Reverse direction: Canvas publish must not shadow an existing Page ──

    public function test_canvas_publish_rejects_a_draft_that_would_shadow_an_existing_page(): void
    {
        Page::create([
            'title' => 'Pre-existing Content Page', 'slug' => 'pre-existing-page', 'body' => '',
            'is_published' => true, 'show_in_menu' => false, 'order' => 1,
        ]);

        $originalActive = Setting::get('active_canvas_theme_data');

        Setting::set('canvas_draft_data', json_encode([
            'pages' => [
                'home' => ['title' => 'Home', 'slug' => 'home', 'is_home' => true, 'blocks' => []],
                'pre-existing-page' => ['title' => 'Colliding Canvas Page', 'slug' => 'pre-existing-page', 'is_home' => false, 'blocks' => []],
            ],
            'header_blocks' => [], 'footer_blocks' => [], 'global_styles' => [],
        ], JSON_UNESCAPED_UNICODE), 'theme');

        $response = $this->actingAs($this->createAdmin())->postJson(route('admin.theme-design.canvas-publish'));

        $response->assertStatus(409);
        $response->assertJson(['success' => false]);
        $this->assertStringContainsString('pre-existing-page', $response->json('error'));

        // Fails closed: the live theme data was never overwritten with the
        // colliding draft, and the existing Page row is untouched.
        $this->assertSame($originalActive, Setting::get('active_canvas_theme_data'));
        $this->assertDatabaseHas('pages', ['slug' => 'pre-existing-page', 'title' => 'Pre-existing Content Page']);
    }

    public function test_canvas_publish_still_succeeds_when_no_page_collides(): void
    {
        Setting::set('canvas_draft_data', json_encode([
            'pages' => [
                'home' => ['title' => 'Home', 'slug' => 'home', 'is_home' => true, 'blocks' => []],
            ],
            'header_blocks' => [], 'footer_blocks' => [], 'global_styles' => [],
        ], JSON_UNESCAPED_UNICODE), 'theme');

        $response = $this->actingAs($this->createAdmin())->postJson(route('admin.theme-design.canvas-publish'));

        $response->assertSuccessful();
        $response->assertJson(['success' => true]);
    }

    // ── Public-page "Edit this page" toolbar must route to the real owner ──

    public function test_canvas_owned_public_page_edit_toolbar_routes_to_theme_design_canvas(): void
    {
        $response = $this->actingAs($this->createAdmin())->get('/page/ext-bridge');

        $response->assertOk();
        $response->assertSee(route('admin.theme-design.canvas-edit') . '?page=ext-bridge', false);
        $response->assertDontSee('ویرایش این صفحه در Builder');
    }

    public function test_canvas_owned_homepage_edit_toolbar_routes_to_theme_design_canvas(): void
    {
        $response = $this->actingAs($this->createAdmin())->get('/');

        $response->assertOk();
        $response->assertSee(route('admin.theme-design.canvas-edit') . '?page=home', false);
    }

    public function test_page_owned_public_page_edit_link_still_routes_to_page_builder(): void
    {
        // A non-colliding Page Builder page (delegated path, PageController)
        // must keep routing to its own real editor — this task's fix must
        // not regress the case that already worked.
        $page = Page::create([
            'title' => 'Independent Page', 'slug' => 'independent-page', 'body' => '',
            'builder_content_v2' => ['blocks' => []], 'builder_type' => Page::BUILDER_TYPE_BUILDER,
            'is_published' => true, 'show_in_menu' => false,
        ]);

        $response = $this->actingAs($this->createAdmin())->get('/page/independent-page');

        $response->assertOk();
        $response->assertSee(route('builder.edit', $page), false);
    }

    // ── Detection of pre-existing (legacy) collisions ──

    public function test_audit_command_detects_a_pre_existing_collision_read_only(): void
    {
        Page::create([
            'title' => 'Legacy Colliding Page', 'slug' => 'ext-bridge', 'body' => '',
            'is_published' => true, 'show_in_menu' => false, 'order' => 1,
        ]);

        $this->artisan('pages:audit-canvas-collisions')
            ->assertFailed();

        // Read-only: neither the Page nor the active canvas theme changed.
        $this->assertDatabaseHas('pages', ['slug' => 'ext-bridge', 'title' => 'Legacy Colliding Page']);
    }

    public function test_audit_command_passes_when_no_collision_exists(): void
    {
        $this->artisan('pages:audit-canvas-collisions')
            ->assertSuccessful();
    }
}

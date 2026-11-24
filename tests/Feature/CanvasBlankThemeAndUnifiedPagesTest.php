<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CanvasBlankThemeAndUnifiedPagesTest extends TestCase
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

    protected function createAdmin(): User
    {
        $user = User::factory()->create();
        $user->update(['is_admin' => true]);
        return $user;
    }

    public function test_admin_can_create_blank_canvas_theme_from_scratch(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        // Initially no canvas theme is active
        Setting::set('active_canvas_theme_data', '', 'theme');
        $this->assertEmpty(Setting::get('active_canvas_theme_data'));

        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('admin.theme-design.canvas.create-blank'), [
            'theme_name' => 'قالب سفارشی بوم خالی',
        ]);

        $response->assertRedirect(route('admin.theme-design.canvas-edit'));
        $response->assertSessionHas('success');

        $activeRaw = Setting::get('active_canvas_theme_data');
        $this->assertNotEmpty($activeRaw);

        $decoded = json_decode($activeRaw, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('pages', $decoded);
        $this->assertArrayHasKey('home', $decoded['pages']);
        $this->assertArrayHasKey('header_blocks', $decoded);
        $this->assertArrayHasKey('footer_blocks', $decoded);
        $this->assertArrayHasKey('global_styles', $decoded);
        $this->assertSame('قالب سفارشی بوم خالی', Setting::get('active_canvas_theme_name'));
    }

    public function test_canvas_edit_automatically_bootstraps_blank_canvas_when_page_param_provided(): void
    {
        // When no active canvas theme exists, visiting canvas-edit with ?page=about must auto-bootstrap
        Setting::set('active_canvas_theme_data', '', 'theme');
        $this->assertEmpty(Setting::get('active_canvas_theme_data'));

        $admin = $this->createAdmin();

        $page = Page::create([
            'title' => 'درباره ما',
            'slug' => 'about-us',
            'body' => 'محتوای صفحه درباره ما',
            'is_published' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.theme-design.canvas-edit', ['page' => 'about-us']));

        $response->assertOk();
        $this->assertNotEmpty(Setting::get('active_canvas_theme_data'));
        $response->assertSee('درباره ما');
    }

    public function test_canvas_can_edit_and_publish_database_pages_without_collision_error(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $admin = $this->createAdmin();

        // Step 1: Create a page in Pages module
        $page = Page::create([
            'title' => 'خدمات ویژه',
            'slug' => 'special-services',
            'body' => 'متن اولیه خدمات',
            'is_published' => true,
        ]);

        // Step 2: Initialize blank canvas
        $this->actingAs($admin)->post(route('admin.theme-design.canvas.create-blank'));

        // Step 3: Save blocks for this page in Canvas Editor
        $saveResponse = $this->actingAs($admin)->putJson(route('admin.theme-design.canvas-save-blocks'), [
            'page_key' => 'special-services',
            'blocks' => [
                [
                    'id' => 'hero-block-1',
                    'type' => 'text',
                    'props' => ['content' => 'بلوک طراحی شده با کانواس'],
                ],
            ],
        ]);

        $saveResponse->assertOk();
        $saveResponse->assertJson(['success' => true]);

        // Verify Page model was updated to builder type
        $page->refresh();
        $this->assertSame(Page::BUILDER_TYPE_BUILDER, $page->builder_type);
        $this->assertNotEmpty($page->builder_content_v2);

        // Step 4: Publish Canvas — MUST NOT throw 409 collision error
        $publishResponse = $this->actingAs($admin)->postJson(route('admin.theme-design.canvas-publish'));

        $publishResponse->assertOk();
        $publishResponse->assertJson(['success' => true]);

        // Step 5: Verify public page render includes the canvas layout
        $publicResponse = $this->get('/page/special-services');
        $publicResponse->assertOk();
        $publicResponse->assertSee('بلوک طراحی شده با کانواس');
    }
}

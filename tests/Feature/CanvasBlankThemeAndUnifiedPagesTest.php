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

    public function test_default_theme_renders_home_and_db_pages_cleanly_when_no_canvas_theme_is_active(): void
    {
        // 1. Ensure active_canvas_theme_data is empty (Zero theme installed)
        Setting::set('active_canvas_theme_data', '', 'theme');
        Setting::set('active_canvas_theme_name', '', 'theme');
        Setting::set('active_canvas_theme_slug', '', 'theme');
        \Illuminate\Support\Facades\Cache::forget('canvas_theme_data');

        // Create a published database page
        $page = Page::create([
            'title' => 'تماس با ما ویژه',
            'slug' => 'contact-special',
            'body' => 'متن رسمی صفحه تماس با ما در تم پیش‌فرض',
            'is_published' => true,
            'show_in_menu' => true,
        ]);

        // 2. Public Home route GET / should render 200 OK with default layout
        $homeResponse = $this->get('/');
        $homeResponse->assertOk();
        $homeResponse->assertSee(\App\Helpers\SiteHelper::siteName());

        // 3. Public DB page route GET /page/contact-special should render 200 OK with default layout
        $pageResponse = $this->get('/page/contact-special');
        $pageResponse->assertOk();
        $pageResponse->assertSee('تماس با ما ویژه');
        $pageResponse->assertSee('متن رسمی صفحه تماس با ما در تم پیش‌فرض');

        // 4. Admin surfaces work without error in default theme mode
        $admin = $this->createAdmin();

        $adminPagesResponse = $this->actingAs($admin)->get(route('admin.pages.index'));
        $adminPagesResponse->assertOk();
        $adminPagesResponse->assertSee('تماس با ما ویژه');

        $adminCanvasResponse = $this->actingAs($admin)->get(route('admin.theme-design.canvas'));
        $adminCanvasResponse->assertOk();
        $adminCanvasResponse->assertSee('قالب Canvas فعال نیست');
        $adminCanvasResponse->assertSee('شروع با بوم خالی');
    }

    public function test_transition_from_default_theme_to_canvas_theme_and_graceful_fallback_on_deactivation(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        // 1. Start with Default Theme (no canvas active)
        Setting::set('active_canvas_theme_data', '', 'theme');
        \Illuminate\Support\Facades\Cache::forget('canvas_theme_data');

        $initialHome = $this->get('/');
        $initialHome->assertOk();

        // 2. Install / assign a Canvas theme (e.g. baseline with custom header & home)
        $admin = $this->createAdmin();
        $canvasData = \App\Services\ThemeCanvas\CanvasCleanSlate::baselineDocument();
        $canvasData['pages']['home']['blocks'] = [
            [
                'id' => 'c-hero-1',
                'type' => 'text',
                'props' => ['content' => 'محتوای صفحه اصلی قالب بوم'],
            ],
        ];

        app(\App\Services\ThemeCanvas\ThemeInstanceRevisionService::class)->record(
            $canvasData,
            \App\Models\ThemeInstanceRevision::TYPE_INSTALL,
            [
                'theme_identifier' => 'test-theme',
                'theme_name'       => 'قالب تستی بوم',
                'active_theme_id'  => 'test-theme',
            ]
        );
        \Illuminate\Support\Facades\Cache::forget('canvas_theme_data');

        // 3. Home now renders through Canvas theme
        $canvasHome = $this->get('/');
        $canvasHome->assertOk();
        $canvasHome->assertSee('محتوای صفحه اصلی قالب بوم');

        // 4. Deactivate Canvas theme -> must gracefully fall back to default theme
        $deactivateResponse = $this->actingAs($admin)->post(route('admin.theme-design.canvas-deactivate'));
        $deactivateResponse->assertRedirect(route('admin.theme-design.canvas'));
        $deactivateResponse->assertSessionHas('success');

        $this->assertEmpty(Setting::get('active_canvas_theme_data'));

        // 5. Public site immediately and cleanly falls back to default theme
        $fallbackHome = $this->get('/');
        $fallbackHome->assertOk();
        $fallbackHome->assertDontSee('محتوای صفحه اصلی قالب بوم');
        $fallbackHome->assertSee(\App\Helpers\SiteHelper::siteName());
    }
}

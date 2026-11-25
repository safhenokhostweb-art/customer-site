<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureLicenseValid;
use App\Http\Middleware\RedirectIfNotInstalled;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\ContentAiAssistantService;
use App\Services\ContentTranslationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CustomerWorkspaceFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([RedirectIfNotInstalled::class, EnsureLicenseValid::class]);
    }

    private function adminUser(): User
    {
        $user = User::create([
            'name'      => 'Admin',
            'mobile'    => '0912' . random_int(1000000, 9999999),
            'password'  => Hash::make('password'),
            'is_admin'  => true,
            'is_active' => true,
        ]);
        if ($role = Role::where('slug', 'super_admin')->first()) {
            $user->roles()->attach($role->id);
        }

        return $user;
    }

    public function test_sidebar_and_menu_edit_preserve_scroll_and_selected_item(): void
    {
        Setting::set('site_installed', '1');
        $admin = $this->adminUser();
        $this->actingAs($admin);

        $menu = Menu::create([
            'name'     => 'منوی اصلی',
            'slug'     => 'main-menu',
            'location' => 'header',
            'status'   => 'published',
        ]);
        $item = MenuItem::create([
            'menu_id' => $menu->id,
            'title'   => 'خانه',
            'type'    => MenuItem::TYPE_CUSTOM_URL,
            'url'     => '/',
            'order'   => 0,
        ]);

        $this->get(route('admin.menus.edit', $menu))
            ->assertOk()
            ->assertSee('customer-admin-sidebar-scroll', false)
            ->assertSee('form[data-preserve-scroll]', false)
            ->assertSee('menu_edit_open_item_' . $menu->id, false);

        $this->put(route('admin.menus.items.update', [$menu, $item]), [
            'title' => 'صفحه اصلی',
            'type'  => MenuItem::TYPE_CUSTOM_URL,
            'url'   => '/',
        ])
            ->assertSessionHas('edited_item_id', $item->id)
            ->assertRedirect();
    }

    public function test_ai_services_use_gapgpt_exclusively(): void
    {
        $this->assertArrayHasKey('gapgpt', config('ai_assistant'));
        $this->assertArrayNotHasKey('deepseek', config('ai_assistant'));
        $this->assertArrayNotHasKey('claude', config('ai_assistant'));

        config(['ai_assistant.gapgpt.api_key' => 'test-gapgpt-key']);
        config(['ai_assistant.gapgpt.base_url' => 'https://api.gapgpt.app/v1']);
        config(['ai_assistant.gapgpt.model' => 'gpt-4o-mini']);

        Http::fake([
            'https://api.gapgpt.app/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'title'            => 'عنوان تست',
                            'html_body'        => '<h2>بخش اول</h2><p>متن تست</p>',
                            'excerpt'          => 'خلاصه تست',
                            'meta_description' => 'توضیح متا',
                            'focus_keyword'    => 'تست',
                            'tags'             => ['تست'],
                            'image_keywords'   => ['solar'],
                        ], JSON_UNESCAPED_UNICODE),
                    ],
                ]],
            ], 200),
        ]);

        $res = app(ContentAiAssistantService::class)->generateSeoContent('article', 'تست هوش مصنوعی');
        $this->assertTrue($res['success']);
        $this->assertSame('gapgpt', $res['provider']);

        $translated = app(ContentTranslationService::class)->translateAndStore(Page::class, 1, 'title', 'سلام', 'en');
        $this->assertTrue($translated);

        Http::assertSentCount(2);
        Http::assertNotSent(fn ($req) => str_contains($req->url(), 'anthropic.com') || str_contains($req->url(), 'deepseek.com'));
    }

    public function test_maintenance_mode_renders_standalone_canvas_without_chrome_for_visitors_and_full_site_for_admin(): void
    {
        Setting::set('site_installed', '1');
        // Zero Theme Dependency: ensure active_canvas_theme_data is empty
        Setting::where('key', 'active_canvas_theme_data')->delete();

        $admin = $this->adminUser();
        $this->actingAs($admin);

        // Settings page shows Maintenance Mode toggle & Canvas button
        $this->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('maintenance-mode-settings')
            ->assertSee('maintenance_mode_enabled')
            ->assertSee(route('admin.settings.maintenance-canvas'));

        // Canvas editor opens maintenance mode even with NO theme installed and supports 3-zone tree reparenting
        $this->get(route('admin.theme-design.canvas-edit', ['mode' => 'maintenance', 'page' => 'maintenance']))
            ->assertOk()
            ->assertSee('mode=maintenance', false)
            ->assertSee('computeTreeDropPosition', false)
            ->assertSee('زیرمجموعه داخل این ویجت')
            ->assertSee('tb-canvas-reset-maintenance-button', false);

        // Live Preview in maintenance mode renders default professional template without active theme
        $this->get(route('admin.theme-design.canvas-live-preview', ['mode' => 'maintenance', 'page' => 'maintenance']))
            ->assertOk()
            ->assertSee('در حال به‌روزرسانی زیرساخت و ارتقای تجربه کاربری')
            ->assertSee('پشتیبانی تلفنی');

        // Save custom maintenance block in Canvas and verify Live Preview renders it immediately
        $this->putJson(route('admin.theme-design.canvas-save-blocks', ['mode' => 'maintenance']), [
            'page_key' => 'maintenance',
            'blocks'   => [[
                'id'    => 'maint-custom-1',
                'type'  => 'hero',
                'props' => [
                    'title'    => 'در حال ارتقای سرورها هستیم',
                    'subtitle' => 'به‌زودی بازمی‌گردیم',
                ],
            ]],
        ])->assertOk()->assertJsonPath('success', true);

        $this->get(route('admin.theme-design.canvas-live-preview', ['mode' => 'maintenance', 'page' => 'maintenance']))
            ->assertOk()
            ->assertSee('در حال ارتقای سرورها هستیم');

        // Enable maintenance mode via Settings
        $this->post(route('admin.settings.update'), [
            'site_name'                 => 'سایت آزمایشی',
            'theme'                     => 'default',
            'maintenance_mode_enabled'  => '1',
        ])->assertSessionHas('success');

        // Logged-in admin still sees the normal site + reminder banner
        $adminResponse = $this->get('/');
        $adminResponse->assertOk();
        $adminResponse->assertSee('maintenance-admin-banner', false);

        // Public visitor (logged out) sees 503 standalone Canvas maintenance page WITHOUT header or footer
        auth()->logout();
        $visitorResponse = $this->get('/');
        $visitorResponse->assertStatus(503);
        $visitorResponse->assertSee('در حال ارتقای سرورها هستیم');
        $visitorResponse->assertDontSee('id="site-header"', false);
        $visitorResponse->assertDontSee('id="site-footer"', false);
        $visitorResponse->assertDontSee('class="canvas-site-header', false);
        $visitorResponse->assertDontSee('class="canvas-site-footer', false);
    }
}

<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SaaSPlatform\ThemeRuntime\Header\HeaderDataResolver;
use SaaSPlatform\ThemeRuntime\Header\SharedHeaderSchema;
use Tests\TestCase;

class CanvasHeaderMenuSourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        @touch(storage_path('installed.lock'));
        config(['site.owner_site' => true]);
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    public function test_canvas_editor_view_receives_customer_menus_and_published_pages(): void
    {
        $user = User::factory()->create(['is_admin' => 1]);

        $menu = Menu::create([
            'name' => 'منوی اصلی سایت',
            'location' => 'primary',
            'is_published' => true,
        ]);
        MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'خانه',
            'url' => '/',
            'order' => 1,
            'is_active' => true,
        ]);

        $page = Page::create([
            'title' => 'درباره ما',
            'slug' => 'about-us',
            'status' => 'published',
            'content' => '<p>محتوا</p>',
        ]);

        $response = $this->actingAs($user)->get(route('admin.theme-design.canvas-edit') . '?create_blank=1');
        $response->assertStatus(200);
        $response->assertViewHas('customerMenus');
        $response->assertViewHas('customerPages');
        $response->assertSee('window.CANVAS_AVAILABLE_MENUS', false);
        $response->assertSee('window.CANVAS_PUBLISHED_PAGES', false);

        $menus = $response->viewData('customerMenus');
        $this->assertNotEmpty($menus);
        $this->assertStringContainsString('منوی اصلی سایت', $menus[0]['name']);

        $pages = $response->viewData('customerPages');
        $this->assertNotEmpty($pages);
        $this->assertSame('درباره ما', $pages[0]['title']);
    }

    public function test_header_resolver_resolves_menu_module_and_pages_module_seamlessly(): void
    {
        $menu = Menu::create([
            'name' => 'منوی هدر',
            'location' => 'header_nav',
            'is_published' => true,
        ]);
        MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'فروشگاه الکترونیک',
            'url' => '/shop',
            'icon' => 'shopping-cart',
            'order' => 1,
            'is_active' => true,
        ]);

        Page::create([
            'title' => 'خدمات ما',
            'slug' => 'services',
            'status' => 'published',
            'content' => '<p>خدمات</p>',
        ]);

        // 1. Resolve menu_module
        $menuData = HeaderDataResolver::resolve([
            'menu_source' => 'menu_module',
            'selected_menu_id' => $menu->id,
        ]);
        $this->assertSame('menu_module', $menuData['navigation']['source']);
        $this->assertNotEmpty($menuData['navigation']['items']);
        $this->assertSame('فروشگاه الکترونیک', $menuData['navigation']['items'][0]['label']);
        $this->assertSame('/shop', $menuData['navigation']['items'][0]['url']);

        // 2. Resolve pages_module
        $pageData = HeaderDataResolver::resolve([
            'menu_source' => 'pages_module',
            'selected_page_ids' => ['services'],
        ]);
        $this->assertSame('pages_module', $pageData['navigation']['source']);
        $this->assertNotEmpty($pageData['navigation']['items']);
        $this->assertSame('خدمات ما', $pageData['navigation']['items'][0]['label']);
        $this->assertStringContainsString('services', $pageData['navigation']['items'][0]['url']);
    }
}

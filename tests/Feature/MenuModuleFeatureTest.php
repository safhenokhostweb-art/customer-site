<?php

namespace Tests\Feature;

use App\Models\License;
use App\Models\ModuleChangeLog;
use App\Models\ModuleVersion;
use App\Models\Setting;
use App\Models\User;
use App\Services\PlatformUxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuModuleFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    public function test_platform_ux_service_uses_local_menus_in_local_mode(): void
    {
        Setting::set('menu_mode', 'local');
        Setting::set('menu_custom_menus', [
            'header' => [
                'items' => [
                    ['title' => 'خانه', 'url' => '/', 'target' => '_self'],
                ],
            ],
            'footer' => [
                'items' => [
                    ['title' => 'تماس', 'url' => '/contact', 'target' => '_self'],
                ],
            ],
        ]);

        $menus = app(PlatformUxService::class)->getMenus();

        $this->assertIsArray($menus);
        $this->assertSame('خانه', $menus['header']['items'][0]['title'] ?? null);
        $this->assertSame('/contact', $menus['footer']['items'][0]['url'] ?? null);
    }

    public function test_admin_modules_page_shows_menu_professional_status_card(): void
    {
        config(['site.owner_site' => false]);

        License::query()->create([
            'license_key' => 'LIC-MENU-001',
            'domain' => 'localhost',
            'expires_at' => now()->addDays(30),
            'modules' => ['menu'],
            'platform_valid' => true,
            'last_validated_at' => now(),
        ]);

        ModuleVersion::setVersion('menu', '1.0.0');
        ModuleChangeLog::query()->create([
            'module_slug' => 'menu',
            'version' => '1.1.0',
            'release_type' => 'minor',
            'changelog' => 'ارتقای حرفه‌ای ماژول منو',
            'released_at' => now()->subHour(),
            'synced_at' => now(),
        ]);

        Setting::set('menu_mode', 'local');
        Setting::set('menu_custom_menus', [
            'header' => ['items' => [['title' => 'خانه', 'url' => '/', 'target' => '_self']]],
            'footer' => ['items' => [['title' => 'تماس', 'url' => '/contact', 'target' => '_self']]],
        ]);

        $admin = User::factory()->create(['is_admin' => true]);
        $response = $this->actingAs($admin)->get(route('admin.modules.index'));

        $response->assertOk();
        $response->assertSee('وضعیت حرفه‌ای ماژول منو');
        $response->assertSee('حالت فعلی منو');
        $response->assertSee('نسخه نصب‌شده');
    }
}


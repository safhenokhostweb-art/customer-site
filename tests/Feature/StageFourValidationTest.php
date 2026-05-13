<?php

namespace Tests\Feature;

use App\Models\ModuleChangeLog;
use App\Models\ModuleVersion;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsValidLicenseWithShop;
use Tests\TestCase;

class StageFourValidationTest extends TestCase
{
    use RefreshDatabase, SeedsValidLicenseWithShop;

    /**
     * Wave 7.0a — License seeding is deliberately deferred to per-test
     * methods because the first test
     * (`admin_route_redirects_to_license_when_license_missing`) asserts the
     * redirect behavior **when no license exists**; seeding one in setUp
     * would invalidate that assertion. Other tests that need a license
     * call `$this->seedValidLicense([...])` directly.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->markSiteAsInstalled();
    }

    public function test_admin_route_redirects_to_license_when_license_missing(): void
    {
        config(['site.owner_site' => false]);

        $admin = User::factory()->create(['is_admin' => true]);
        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.license.index'));
    }

    public function test_admin_route_allows_access_with_valid_license(): void
    {
        config(['site.owner_site' => false]);

        $this->seedValidLicense(['blog', 'shop']);

        $admin = User::factory()->create(['is_admin' => true]);
        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
    }

    public function test_payment_callback_rejects_malformed_input(): void
    {
        config(['site.platform_url' => 'https://panel.example.test']);

        $response = $this->get(route('payment.callback', [
            'invoice' => '../bad',
            'Authority' => 'A?123',
            'Status' => 'OK',
        ]));

        $response->assertRedirect('https://panel.example.test/store/cart');
        $response->assertSessionHas('error');
    }

    public function test_payment_callback_forwards_valid_input(): void
    {
        config(['site.platform_url' => 'https://panel.example.test']);

        $response = $this->get(route('payment.callback', [
            'invoice' => 'INV_1001',
            'Authority' => 'A12345',
            'Status' => 'OK',
        ]));

        $response->assertRedirect('https://panel.example.test/checkout/callback/INV_1001?Authority=A12345&Status=OK');
    }

    public function test_public_routes_home_page_blog_shop_work_with_valid_modules(): void
    {
        config(['site.owner_site' => false]);

        $this->seedValidLicense(['blog', 'shop']);

        Page::query()->create([
            'title' => 'Test Page',
            'slug' => 'test-page',
            'body' => 'test body',
            'is_published' => true,
            'show_in_menu' => true,
            'order' => 1,
        ]);

        $this->get(route('home'))->assertOk();
        $this->get(route('page.show', ['slug' => 'test-page']))->assertOk();
        $this->get(route('blog.index'))->assertOk();
        $this->get(route('shop.index'))->assertOk();
    }

    public function test_admin_modules_page_shows_latest_synced_blog_release(): void
    {
        config(['site.owner_site' => false]);

        $this->seedValidLicense(['blog']);

        ModuleVersion::setVersion('blog', '1.0.0');
        ModuleChangeLog::query()->create([
            'module_slug' => 'blog',
            'version' => '1.1.0',
            'release_type' => 'minor',
            'changelog' => 'بهبود لیست و صفحه مطلب وبلاگ',
            'released_at' => now()->subHour(),
            'synced_at' => now(),
        ]);

        $admin = User::factory()->create(['is_admin' => true]);
        $response = $this->actingAs($admin)->get(route('admin.modules.index'));

        $response->assertOk();
        $response->assertSee('نسخه نصب‌شده');
        $response->assertSee('v1.1.0');
    }
}

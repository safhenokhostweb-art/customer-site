<?php

namespace Tests\Feature;

use App\Models\License;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StageFourValidationTest extends TestCase
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

        License::query()->create([
            'license_key' => 'LIC-VALID-001',
            'domain' => 'localhost',
            'expires_at' => now()->addDays(30),
            'modules' => ['blog', 'shop'],
            'platform_valid' => true,
            'last_validated_at' => now(),
        ]);

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

        License::query()->create([
            'license_key' => 'LIC-PUBLIC-001',
            'domain' => 'localhost',
            'expires_at' => now()->addDays(30),
            'modules' => ['blog', 'shop'],
            'platform_valid' => true,
            'last_validated_at' => now(),
        ]);

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
}

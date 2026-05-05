<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleCouponTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        config(['site.owner_site' => true, 'modules.modules.coupon.default' => true]);
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    public function test_coupon_index_requires_auth(): void
    {
        $response = $this->get(route('admin.coupons.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_coupon_crud(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $response = $this->get(route('admin.coupons.index'));
        $response->assertStatus(200);

        $response = $this->post(route('admin.coupons.store'), [
            'code' => 'TEST20',
            'discount_type' => 'percent',
            'discount_percent' => 20,
            'is_active' => true,
        ]);
        $response->assertRedirect(route('admin.coupons.index'));
        $this->assertDatabaseHas('coupons', ['code' => 'TEST20', 'discount_percent' => 20]);

        $coupon = Coupon::first();
        $response = $this->put(route('admin.coupons.update', $coupon), [
            'code' => 'TEST25',
            'discount_type' => 'percent',
            'discount_percent' => 25,
            'is_active' => true,
        ]);
        $response->assertRedirect(route('admin.coupons.index'));
        $this->assertDatabaseHas('coupons', ['code' => 'TEST25']);

        $response = $this->delete(route('admin.coupons.destroy', $coupon));
        $response->assertRedirect(route('admin.coupons.index'));
        $this->assertDatabaseMissing('coupons', ['id' => $coupon->id]);
    }
}

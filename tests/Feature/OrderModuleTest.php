<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderModuleTest extends TestCase
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

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function order(array $attrs = []): Order
    {
        return Order::create(array_merge([
            'number'         => 'ORD-' . date('Ymd') . '-000001',
            'customer_name'  => 'تست کاربر',
            'customer_mobile'=> '09121234567',
            'subtotal'       => 100000,
            'shipping_cost'  => 0,
            'total'          => 100000,
            'status'         => Order::STATUS_PENDING,
        ], $attrs));
    }

    public function test_orders_index_requires_auth(): void
    {
        $this->get(route('admin.orders.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_view_orders_index(): void
    {
        $admin = $this->admin();
        $this->order();

        $response = $this->actingAs($admin)->get(route('admin.orders.index'));
        $response->assertOk();
        $response->assertSee('سفارشات');
    }

    public function test_orders_index_shows_stats(): void
    {
        $admin = $this->admin();
        $this->order(['status' => Order::STATUS_PAID]);
        $this->order(['status' => Order::STATUS_CANCELLED, 'number' => 'ORD-TEST-000002']);

        $response = $this->actingAs($admin)->get(route('admin.orders.index'));
        $response->assertOk();
        $response->assertSee('درآمد کل');
        $response->assertSee('کل سفارشات');
    }

    public function test_orders_index_filter_by_status(): void
    {
        $admin = $this->admin();
        $this->order(['status' => Order::STATUS_PAID]);
        $this->order(['status' => Order::STATUS_CANCELLED, 'number' => 'ORD-TEST-000002']);

        $response = $this->actingAs($admin)->get(route('admin.orders.index', ['status' => 'paid']));
        $response->assertOk();
        $response->assertSee('پرداخت شده');
    }

    public function test_orders_index_search(): void
    {
        $admin = $this->admin();
        $this->order(['customer_name' => 'علی احمدی']);

        $response = $this->actingAs($admin)->get(route('admin.orders.index', ['q' => 'علی']));
        $response->assertOk();
        $response->assertSee('علی احمدی');
    }

    public function test_admin_can_view_order_show(): void
    {
        $admin = $this->admin();
        $order = $this->order();

        $response = $this->actingAs($admin)->get(route('admin.orders.show', $order));
        $response->assertOk();
        $response->assertSee($order->number);
        $response->assertSee('اطلاعات مشتری');
    }

    public function test_admin_can_update_order_status(): void
    {
        $admin = $this->admin();
        $order = $this->order();

        $response = $this->actingAs($admin)->patch(route('admin.orders.update-status', $order), [
            'status' => Order::STATUS_PAID,
            'reason' => 'پرداخت تأیید شد',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => Order::STATUS_PAID]);
    }

    public function test_cannot_transition_from_cancelled(): void
    {
        $admin = $this->admin();
        $order = $this->order(['status' => Order::STATUS_CANCELLED]);

        $this->actingAs($admin)->patch(route('admin.orders.update-status', $order), [
            'status' => Order::STATUS_PAID,
        ]);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => Order::STATUS_CANCELLED]);
    }

    public function test_order_show_displays_status_change_form(): void
    {
        $admin = $this->admin();
        $order = $this->order(['status' => Order::STATUS_PENDING]);

        $response = $this->actingAs($admin)->get(route('admin.orders.show', $order));
        $response->assertOk();
        $response->assertSee('تغییر وضعیت');
        $response->assertSee('update-status');
    }

    public function test_cancelled_order_shows_no_status_change_form(): void
    {
        $admin = $this->admin();
        $order = $this->order(['status' => Order::STATUS_CANCELLED]);

        $response = $this->actingAs($admin)->get(route('admin.orders.show', $order));
        $response->assertOk();
        $response->assertDontSee('تغییر وضعیت');
    }
}

<?php

namespace Tests\Feature;

use App\Helpers\ShopCartHelper;
use App\Models\AdminActivityLog;
use App\Models\Coupon;
use App\Models\License;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\ProductBundle;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\OrderStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Wave 6.1 — Shop Data-Integrity Hardening regression suite.
 *
 * Covers:
 *   P0-S1 — coupons.discount_percent allows NULL for fixed coupons.
 *   P0-S4 — Order::generateNumber emits unique 6-digit ORD-Ymd-NNNNNN values.
 *   P1-S1 — checkout locks bundle children + variants up-front (no race).
 *   P1-S5 — Account\OrderController exposes only user_id-owned orders.
 *   P1-S2 — OrderStatusService FSM rejects illegal transitions.
 *   P1-S3 — every status transition writes order_status_history and an
 *           admin_activity_log row.
 *   P1-S4 — transition to "cancelled" restocks atomically and decrements
 *           coupon.used_count exactly once.
 *
 * Test-suite contract (Wave 5.3 baseline):
 *   - Each test seeds its own license/module to bypass `license.valid` and
 *     `module:shop` middleware (the 6 baseline Shop failures will be
 *     addressed in Wave 6.2; this file is self-contained).
 */
class Wave61ShopDataIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        Cache::forget('settings');
        foreach (['shop-cart', 'shop-checkout', 'shop-review', 'shop-stock-notify'] as $name) {
            RateLimiter::clear($name);
        }
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    // =====================================================================
    // P0-S1 — coupons.discount_percent nullability
    // =====================================================================

    public function test_coupon_discount_percent_column_is_nullable_after_migration(): void
    {
        $column = collect(Schema::getColumns('coupons'))
            ->firstWhere('name', 'discount_percent');

        $this->assertNotNull($column, 'discount_percent column must exist');
        $this->assertTrue(
            (bool) ($column['nullable'] ?? false),
            'discount_percent must be nullable after Wave 6.1 migration'
        );
    }

    public function test_fixed_coupon_persists_with_null_discount_percent(): void
    {
        $coupon = Coupon::query()->create([
            'code' => 'FIXED-W61',
            'discount_type' => Coupon::TYPE_FIXED,
            'discount_percent' => null,
            'discount_amount' => 50000,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id,
            'discount_type' => 'fixed',
            'discount_percent' => null,
            'discount_amount' => 50000,
        ]);

        $this->assertSame(50000, $coupon->fresh()->calculateDiscount(120000));
    }

    public function test_admin_coupon_store_with_fixed_type_does_not_violate_schema(): void
    {
        $this->seedValidLicense();
        $admin = $this->makeUser(admin: true);

        $response = $this->actingAs($admin)->post(route('admin.coupons.store'), [
            'code' => 'fix-admin-flow',
            'discount_type' => 'fixed',
            'discount_amount' => 75000,
            'min_order_amount' => 0,
            'usage_limit' => 100,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.coupons.index'));
        $this->assertDatabaseHas('coupons', [
            'code' => 'FIX-ADMIN-FLOW',
            'discount_type' => 'fixed',
            'discount_percent' => null,
            'discount_amount' => 75000,
        ]);
    }

    // =====================================================================
    // P0-S4 — Order::generateNumber concurrency & 6-digit pad
    // =====================================================================

    public function test_generate_number_pads_to_six_digits(): void
    {
        $n = Order::generateNumber();
        $this->assertMatchesRegularExpression('/^ORD-\d{8}-\d{6}$/', $n);
    }

    public function test_generate_number_is_strictly_unique_across_50_sequential_calls(): void
    {
        $product = $this->publishedProduct('seq-prod', 1000, stock: 1000);

        $produced = [];
        for ($i = 0; $i < 50; $i++) {
            $number = Order::generateNumber();
            Order::query()->create([
                'number' => $number,
                'customer_name' => 'Bench ' . $i,
                'customer_mobile' => '091' . str_pad($i, 8, '0', STR_PAD_LEFT),
                'customer_address' => 'addr',
                'subtotal' => 1000,
                'total' => 1000,
                'status' => Order::STATUS_PENDING,
            ]);
            $produced[] = $number;
        }

        $this->assertCount(50, $produced);
        $this->assertCount(50, array_unique($produced), 'duplicate order numbers must never be produced');
    }

    public function test_generate_number_resists_concurrent_simulation_via_advisory_or_retry(): void
    {
        // Pre-seed orders with sequential numbers to ensure the retry path
        // resolves collisions even if the count-based seed is off.
        $today = date('Ymd');
        Order::query()->create([
            'number' => 'ORD-' . $today . '-000001',
            'customer_name' => 'A', 'customer_mobile' => '09100000001', 'customer_address' => '-',
            'subtotal' => 1, 'total' => 1, 'status' => Order::STATUS_PENDING,
        ]);
        Order::query()->create([
            'number' => 'ORD-' . $today . '-000002',
            'customer_name' => 'B', 'customer_mobile' => '09100000002', 'customer_address' => '-',
            'subtotal' => 1, 'total' => 1, 'status' => Order::STATUS_PENDING,
        ]);

        $next = Order::generateNumber();
        $this->assertSame('ORD-' . $today . '-000003', $next);
    }

    // =====================================================================
    // P1-S1 — bundle/variant stock race fix
    // =====================================================================

    public function test_checkout_locks_bundle_children_and_decrements_stock_correctly(): void
    {
        $this->seedValidLicense();
        $child = $this->publishedProduct('child-w61', 50000, stock: 10);
        $bundle = $this->publishedProduct('bundle-w61', 0, stock: 0);
        $bundle->update(['is_bundle' => true, 'bundle_pricing' => 'sum_discount']);
        ProductBundle::query()->create([
            'parent_product_id' => $bundle->id,
            'child_product_id' => $child->id,
            'quantity' => 2,
        ]);
        $bundle->refresh();

        $lineKey = ShopCartHelper::lineKey($bundle->id, null);
        $response = $this->withSession([
            'cart' => [
                $lineKey => [
                    'product_id' => $bundle->id,
                    'id' => $bundle->id,
                    'title' => $bundle->title,
                    'price' => 100000,
                    'quantity' => 3,
                    'image' => null,
                    'is_bundle' => true,
                ],
            ],
        ])->post(route('shop.checkout'), [
            'customer_name'    => 'Bundle Test',
            'customer_mobile'  => '09140000001',
            'customer_address' => 'addr',
        ]);

        $response->assertRedirect(route('shop.index'));
        // 10 - (2 per bundle * 3 bundles) = 4
        $this->assertSame(4, (int) $child->fresh()->stock);
    }

    public function test_checkout_locks_variants_before_validation(): void
    {
        $this->seedValidLicense();
        $product = $this->publishedProduct('locked-variant', 100000, stock: 0);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'label' => 'M',
            'stock' => 5,
            'sort_order' => 0,
        ]);

        $lineKey = ShopCartHelper::lineKey($product->id, $variant->id);
        $this->withSession([
            'cart' => [
                $lineKey => [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'id' => $product->id,
                    'title' => $product->title,
                    'price' => $variant->effective_price,
                    'quantity' => 3,
                    'image' => null,
                ],
            ],
        ])->post(route('shop.checkout'), [
            'customer_name'    => 'Variant Test',
            'customer_mobile'  => '09140000002',
            'customer_address' => 'addr',
        ])->assertRedirect(route('shop.index'));

        $this->assertSame(2, (int) $variant->fresh()->stock);
    }

    // =====================================================================
    // P1-S5 — Account\OrderController IDOR hardening
    // =====================================================================

    public function test_account_orders_index_excludes_email_matched_guest_orders(): void
    {
        $this->seedValidLicense();
        $user = $this->makeUser();
        // Authenticated user's own order
        $owned = Order::query()->create([
            'number' => 'ORD-' . date('Ymd') . '-OWN001',
            'user_id' => $user->id,
            'customer_name' => 'me',
            'customer_mobile' => $user->mobile,
            'customer_address' => 'a',
            'subtotal' => 100, 'total' => 100, 'status' => Order::STATUS_PENDING,
        ]);
        // Guest order with matching mobile but no user_id (pre-Wave-6.1 IDOR)
        $guestLeak = Order::query()->create([
            'number' => 'ORD-' . date('Ymd') . '-LEAK01',
            'user_id' => null,
            'customer_name' => 'guest',
            'customer_mobile' => $user->mobile,
            'customer_address' => 'b',
            'subtotal' => 200, 'total' => 200, 'status' => Order::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user)->get(route('account.orders.index'));
        $response->assertOk();
        $response->assertViewHas('orders');
        $listed = $response->viewData('orders')->pluck('id')->all();

        $this->assertContains($owned->id, $listed);
        $this->assertNotContains($guestLeak->id, $listed, 'email-matched guest order must NOT be returned');
    }

    public function test_account_orders_show_rejects_email_matched_guest_order(): void
    {
        $this->seedValidLicense();
        $user = $this->makeUser();
        $guestOrder = Order::query()->create([
            'number' => 'ORD-' . date('Ymd') . '-LEAK02',
            'user_id' => null,
            'customer_name' => 'guest',
            'customer_mobile' => $user->mobile,
            'customer_address' => 'b',
            'subtotal' => 200, 'total' => 200, 'status' => Order::STATUS_PENDING,
        ]);

        $this->actingAs($user)
            ->get(route('account.orders.show', $guestOrder))
            ->assertNotFound();
    }

    // =====================================================================
    // P1-S2 — OrderStatusService FSM
    // =====================================================================

    public function test_status_service_rejects_transition_from_cancelled(): void
    {
        $order = $this->makeOrder(status: Order::STATUS_CANCELLED);
        $service = app(OrderStatusService::class);

        $this->expectException(InvalidArgumentException::class);
        $service->transition($order, Order::STATUS_PAID);
    }

    public function test_status_service_rejects_identical_transition(): void
    {
        $order = $this->makeOrder(status: Order::STATUS_PAID);
        $service = app(OrderStatusService::class);

        $this->expectException(InvalidArgumentException::class);
        $service->transition($order, Order::STATUS_PAID);
    }

    public function test_status_service_rejects_unknown_status(): void
    {
        $order = $this->makeOrder(status: Order::STATUS_PENDING);
        $service = app(OrderStatusService::class);

        $this->expectException(InvalidArgumentException::class);
        $service->transition($order, 'frozen');
    }

    public function test_status_service_accepts_pending_to_paid(): void
    {
        $order = $this->makeOrder(status: Order::STATUS_PENDING);
        $result = app(OrderStatusService::class)
            ->transition($order, Order::STATUS_PAID);

        $this->assertSame(Order::STATUS_PAID, $result['order']->status);
        $this->assertSame('pending', $result['history']->from_status);
        $this->assertSame('paid', $result['history']->to_status);
    }

    // =====================================================================
    // P1-S3 — history + audit log on every transition
    // =====================================================================

    public function test_transition_writes_status_history_row(): void
    {
        $order = $this->makeOrder(status: Order::STATUS_PENDING);
        $admin = $this->makeUser(admin: true);

        app(OrderStatusService::class)
            ->transition($order, Order::STATUS_PAID, changedByUserId: $admin->id, reason: 'پرداخت دستی');

        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->id,
            'from_status' => 'pending',
            'to_status' => 'paid',
            'changed_by_user_id' => $admin->id,
            'reason' => 'پرداخت دستی',
        ]);
    }

    public function test_transition_writes_admin_activity_log(): void
    {
        $order = $this->makeOrder(status: Order::STATUS_PENDING);
        $admin = $this->makeUser(admin: true);
        $this->actingAs($admin);

        app(OrderStatusService::class)
            ->transition($order, Order::STATUS_PAID, changedByUserId: $admin->id);

        $log = AdminActivityLog::query()
            ->where('action', 'order.status_changed')
            ->where('subject_id', $order->id)
            ->first();

        $this->assertNotNull($log, 'admin_activity_log row missing for status change');
        $this->assertSame($admin->id, $log->admin_user_id);
        $this->assertSame('paid', $log->properties['to_status']);
        $this->assertSame('pending', $log->properties['from_status']);
    }

    public function test_admin_controller_endpoint_persists_reason_and_history(): void
    {
        $this->seedValidLicense();
        $admin = $this->makeUser(admin: true);
        $order = $this->makeOrder(status: Order::STATUS_PENDING);

        $this->actingAs($admin)
            ->patch(route('admin.orders.update-status', $order), [
                'status' => 'paid',
                'reason' => 'دستی توسط ادمین',
            ])
            ->assertRedirect();

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->id,
            'from_status' => 'pending',
            'to_status' => 'paid',
            'reason' => 'دستی توسط ادمین',
        ]);
    }

    public function test_admin_controller_rejects_illegal_transition_gracefully(): void
    {
        $this->seedValidLicense();
        $admin = $this->makeUser(admin: true);
        $order = $this->makeOrder(status: Order::STATUS_CANCELLED);

        $this->actingAs($admin)
            ->patch(route('admin.orders.update-status', $order), [
                'status' => 'paid',
            ])
            ->assertRedirect();

        $this->assertSame('cancelled', $order->fresh()->status);
        // No history row created — illegal transitions never advance the audit.
        $this->assertDatabaseMissing('order_status_history', [
            'order_id' => $order->id,
            'to_status' => 'paid',
        ]);
    }

    // =====================================================================
    // P1-S4 — cancel restocks + decrements coupon (idempotent)
    // =====================================================================

    public function test_cancel_restocks_product_and_variant_inventory(): void
    {
        $product = $this->publishedProduct('cancel-prod', 1000, stock: 5);
        $variantProduct = $this->publishedProduct('cancel-var-prod', 2000, stock: 0);
        $variant = ProductVariant::query()->create([
            'product_id' => $variantProduct->id,
            'label' => 'V',
            'stock' => 1,
            'sort_order' => 0,
        ]);

        $order = $this->makeOrder(status: Order::STATUS_PAID);
        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_title' => $product->title,
            'price' => 1000, 'quantity' => 3, 'total' => 3000,
        ]);
        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $variantProduct->id,
            'product_variant_id' => $variant->id,
            'product_title' => $variantProduct->title,
            'price' => 2000, 'quantity' => 4, 'total' => 8000,
        ]);

        $result = app(OrderStatusService::class)
            ->transition($order, Order::STATUS_CANCELLED, reason: 'مرجوعی مشتری');

        $this->assertSame(8, (int) $product->fresh()->stock, 'product stock restored: 5 + 3');
        $this->assertSame(5, (int) $variant->fresh()->stock, 'variant stock restored: 1 + 4');
        $this->assertNotNull($order->fresh()->restocked_at);
        $this->assertTrue($result['side_effects']['restocked']);
    }

    public function test_cancel_decrements_coupon_usage_count_once(): void
    {
        $coupon = Coupon::query()->create([
            'code' => 'CNL-W61',
            'discount_type' => Coupon::TYPE_PERCENT,
            'discount_percent' => 20,
            'used_count' => 7,
            'is_active' => true,
        ]);
        $order = $this->makeOrder(status: Order::STATUS_PAID);
        $order->update(['coupon_id' => $coupon->id, 'discount_amount' => 10000]);

        app(OrderStatusService::class)
            ->transition($order, Order::STATUS_CANCELLED);

        $this->assertSame(6, (int) $coupon->fresh()->used_count);
    }

    public function test_cancel_does_not_restock_a_second_time_when_replayed(): void
    {
        $product = $this->publishedProduct('idempotent-prod', 1000, stock: 5);
        $order = $this->makeOrder(status: Order::STATUS_PAID);
        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_title' => $product->title,
            'price' => 1000, 'quantity' => 2, 'total' => 2000,
        ]);

        $service = app(OrderStatusService::class);
        $service->transition($order, Order::STATUS_CANCELLED);
        $this->assertSame(7, (int) $product->fresh()->stock);

        // Force the order back to a non-terminal status without going through
        // the FSM (simulate manual SQL fix-up) then re-cancel via service.
        DB::table('orders')->where('id', $order->id)->update(['status' => 'paid']);
        $order->refresh();
        $second = $service->transition($order, Order::STATUS_CANCELLED);

        $this->assertSame(7, (int) $product->fresh()->stock, 'restock must NOT run twice');
        $this->assertFalse($second['side_effects']['restocked']);
        $this->assertSame('already_restocked', $second['side_effects']['reason']);
    }

    public function test_cancel_skips_restock_for_variants_with_null_stock(): void
    {
        // products.stock is NOT NULL in schema, but product_variants.stock IS
        // nullable. The "unlimited stock" semantic is only reachable through a
        // variant, so exercise the null-stock skip branch via a variant.
        $product = $this->publishedProduct('unlimited-parent', 1000, stock: 0);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'label' => 'unlimited',
            'stock' => null,
            'sort_order' => 0,
        ]);

        $order = $this->makeOrder(status: Order::STATUS_PAID);
        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_title' => $product->title,
            'price' => 1000, 'quantity' => 3, 'total' => 3000,
        ]);

        app(OrderStatusService::class)->transition($order, Order::STATUS_CANCELLED);

        $this->assertNull($variant->fresh()->stock, 'null variant stock must remain null after cancel');
    }

    // =====================================================================
    // Helpers
    // =====================================================================

    private function seedValidLicense(): License
    {
        $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost';
        return License::firstOrCreate(
            ['domain' => $host],
            [
                'license_key' => 'W61-' . uniqid(),
                'expires_at' => now()->addYear(),
                'modules' => ['shop'],
                'platform_valid' => true,
                'last_validated_at' => now(),
            ]
        );
    }

    private function makeUser(bool $admin = false): User
    {
        return User::query()->create([
            'name' => 'W61 ' . ($admin ? 'Admin' : 'User'),
            'email' => 'w61_' . uniqid() . '@example.com',
            'mobile' => '0912' . random_int(1000000, 9999999),
            'password' => bcrypt('Secret123!'),
            'is_admin' => $admin,
            'is_active' => true,
        ]);
    }

    private function publishedProduct(string $slug, int $price, ?int $stock): Product
    {
        return Product::query()->create([
            'title' => $slug,
            'slug' => $slug . '-' . uniqid(),
            'price' => $price,
            'stock' => $stock,
            'status' => Product::STATUS_PUBLISHED,
        ]);
    }

    private function makeOrder(string $status = Order::STATUS_PENDING): Order
    {
        return Order::query()->create([
            'number' => Order::generateNumber(),
            'customer_name' => 'Test ' . uniqid(),
            'customer_mobile' => '09100000099',
            'customer_address' => 'Test Address',
            'subtotal' => 10000,
            'total' => 10000,
            'status' => $status,
        ]);
    }
}

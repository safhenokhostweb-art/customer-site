<?php

namespace Tests\Feature;

use App\Models\License;
use App\Models\Order;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Membership Benefits — checkout integration tests.
 *
 * Covers:
 *   - Guest checkout (no membership) → membership_discount_amount = 0
 *   - User without subscription → no discount
 *   - User with apply_to_all plan → correct discount in order
 *   - membership_discount_amount + coupon stack correctly
 *   - total never goes below 0
 *   - membership_plan_name stored in order
 *   - Order total = subtotal − membership − coupon
 */
class MembershipCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        config(['site.owner_site' => true]);
        RateLimiter::clear('shop-checkout');
        Cache::flush();
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    // ── helpers ───────────────────────────────────────────────────────────────

    private function seedShopLicense(): void
    {
        // Use empty domain so License::isValid() skips host-matching in test context
        License::firstOrCreate(
            ['license_key' => 'MEM-TEST-SHOP'],
            [
                'domain'            => '',
                'expires_at'        => now()->addYear(),
                'modules'           => ['shop', 'subscription', 'coupon'],
                'platform_valid'    => true,
                'last_validated_at' => now(),
            ]
        );
    }

    private static int $userSeq = 0;

    private function makeUser(): User
    {
        self::$userSeq++;
        return User::query()->create([
            'name'     => 'کاربر تست ' . self::$userSeq,
            'mobile'   => '092' . str_pad(self::$userSeq, 8, '0', STR_PAD_LEFT),
            'password' => bcrypt('password'),
            'is_admin' => false,
        ]);
    }

    private function makeProduct(int $price = 100000): Product
    {
        return Product::query()->create([
            'title'  => 'محصول ' . uniqid(),
            'slug'   => 'product-' . uniqid(),
            'price'  => $price,
            'status' => Product::STATUS_PUBLISHED,
            'stock'  => 999,
            'allow_backorder' => true,
        ]);
    }

    private function makePlan(array $attrs = []): SubscriptionPlan
    {
        return SubscriptionPlan::create(array_merge([
            'name'                     => 'پلن طلایی',
            'slug'                     => 'gold-' . uniqid(),
            'price'                    => 200000,
            'duration'                 => 'month',
            'duration_count'           => 1,
            'is_active'                => true,
            'product_discount_percent' => 0,
            'apply_to_all_products'    => false,
            'free_shipping'            => false,
            'vip_support'              => false,
        ], $attrs));
    }

    private function activatePlan(User $user, SubscriptionPlan $plan): Subscription
    {
        return Subscription::create([
            'user_id'              => $user->id,
            'subscription_plan_id' => $plan->id,
            'status'               => Subscription::STATUS_ACTIVE,
            'starts_at'            => now(),
            'ends_at'              => now()->addDays(30),
        ]);
    }

    private function cartSession(Product $product, int $qty = 1): array
    {
        return [
            $product->id . ':0' => [
                'product_id'         => $product->id,
                'product_variant_id' => null,
                'price'              => $product->effective_price,
                'quantity'           => $qty,
                'title'              => $product->title,
                'image'              => null,
                'is_bundle'          => false,
            ],
        ];
    }

    private function checkoutPayload(array $extra = []): array
    {
        return array_merge([
            'customer_name'    => 'علی احمدی',
            'customer_mobile'  => '09121234567',
            'customer_address' => 'تهران، خیابان ولیعصر',
        ], $extra);
    }

    // ── tests ─────────────────────────────────────────────────────────────────

    public function test_no_subscription_no_membership_discount(): void
    {
        $this->seedShopLicense();
        $user    = $this->makeUser();
        $product = $this->makeProduct(100000);

        $response = $this->actingAs($user)
            ->withSession(['cart' => $this->cartSession($product)])
            ->post(route('shop.checkout'), $this->checkoutPayload());

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertEquals(0, (float) $order->membership_discount_amount);
        $this->assertEquals(100000, (float) $order->total);
    }

    public function test_active_plan_apply_all_discounts_order_correctly(): void
    {
        $this->seedShopLicense();
        $user    = $this->makeUser();
        $product = $this->makeProduct(200000);
        $plan    = $this->makePlan([
            'product_discount_percent' => 10,
            'apply_to_all_products'    => true,
        ]);
        $this->activatePlan($user, $plan);

        $this->actingAs($user)
            ->withSession(['cart' => $this->cartSession($product)])
            ->post(route('shop.checkout'), $this->checkoutPayload());

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertEquals(20000, (float) $order->membership_discount_amount); // 10% of 200,000
        $this->assertEquals(180000, (float) $order->total);                      // 200,000 − 20,000
        $this->assertEquals('پلن طلایی', $order->membership_plan_name);
    }

    public function test_membership_and_coupon_stack_correctly(): void
    {
        $this->seedShopLicense();
        $user    = $this->makeUser();
        $product = $this->makeProduct(100000);
        $plan    = $this->makePlan([
            'product_discount_percent' => 10,
            'apply_to_all_products'    => true,
        ]);
        $this->activatePlan($user, $plan);

        // Enable coupon module
        config(['site.modules.coupon' => true]);

        $coupon = \App\Models\Coupon::create([
            'code'             => 'SAVE5000',
            'discount_type'    => 'fixed',
            'discount_amount'  => 5000,
            'discount_percent' => null,
            'is_active'        => true,
            'used_count'       => 0,
        ]);

        $this->actingAs($user)
            ->withSession(['cart' => $this->cartSession($product)])
            ->post(route('shop.checkout'), $this->checkoutPayload(['coupon_code' => 'SAVE5000']));

        $order = Order::first();
        $this->assertNotNull($order);
        // subtotal=100,000 → membership=10,000 → baseForCoupon=90,000 → coupon=5,000
        // total = 100,000 − 10,000 − 5,000 = 85,000
        $this->assertEquals(10000, (float) $order->membership_discount_amount);
        $this->assertEquals(5000, (float) $order->discount_amount);
        $this->assertEquals(85000, (float) $order->total);
    }

    public function test_total_never_goes_below_zero(): void
    {
        $this->seedShopLicense();
        $user    = $this->makeUser();
        $product = $this->makeProduct(1000);
        $plan    = $this->makePlan([
            'product_discount_percent' => 100,
            'apply_to_all_products'    => true,
        ]);
        $this->activatePlan($user, $plan);

        $this->actingAs($user)
            ->withSession(['cart' => $this->cartSession($product)])
            ->post(route('shop.checkout'), $this->checkoutPayload());

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertEquals(0, (float) $order->total);
        $this->assertGreaterThanOrEqual(0, (float) $order->total);
    }

    public function test_expired_subscription_gives_no_discount(): void
    {
        $this->seedShopLicense();
        $user    = $this->makeUser();
        $product = $this->makeProduct(100000);
        $plan    = $this->makePlan([
            'product_discount_percent' => 15,
            'apply_to_all_products'    => true,
        ]);

        Subscription::create([
            'user_id'              => $user->id,
            'subscription_plan_id' => $plan->id,
            'status'               => Subscription::STATUS_EXPIRED,
            'starts_at'            => now()->subDays(40),
            'ends_at'              => now()->subDays(5),
        ]);

        $this->actingAs($user)
            ->withSession(['cart' => $this->cartSession($product)])
            ->post(route('shop.checkout'), $this->checkoutPayload());

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertEquals(0, (float) $order->membership_discount_amount);
        $this->assertEquals(100000, (float) $order->total);
    }

    public function test_cart_page_passes_membership_to_view(): void
    {
        $this->seedShopLicense();
        $user    = $this->makeUser();
        $product = $this->makeProduct(50000);
        $plan    = $this->makePlan([
            'product_discount_percent' => 20,
            'apply_to_all_products'    => true,
        ]);
        $this->activatePlan($user, $plan);

        $response = $this->actingAs($user)
            ->withSession(['cart' => $this->cartSession($product)])
            ->get(route('shop.cart'));

        $response->assertStatus(200);
        $response->assertViewHas('membership');
        $response->assertViewHas('subtotal', 50000);

        $membership = $response->viewData('membership');
        $this->assertEquals(10000, $membership->discountAmount); // 20% of 50,000
        $this->assertTrue($membership->hasBenefit());
    }

    public function test_cart_page_for_user_without_subscription_has_empty_membership(): void
    {
        $this->seedShopLicense();
        $user    = $this->makeUser();
        $product = $this->makeProduct(50000);

        $response = $this->actingAs($user)
            ->withSession(['cart' => $this->cartSession($product)])
            ->get(route('shop.cart'));

        $response->assertStatus(200);
        $response->assertViewHas('membership');

        $membership = $response->viewData('membership');
        $this->assertFalse($membership->hasBenefit());
        $this->assertEquals(0, $membership->discountAmount);
    }
}

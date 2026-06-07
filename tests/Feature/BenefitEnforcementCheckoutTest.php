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
 * P3 — Benefit Enforcement in Shop Checkout
 *
 * Covers:
 *   - product_discount_percent applied → correct membership_discount_amount in order
 *   - free_shipping enforced → shipping_cost = 0 for member, configured amount for non-member
 *   - early_access badge shown in cart view
 *   - vip_support badge shown in cart view
 *   - discount + free_shipping stack: total = subtotal + 0 shipping − discount
 *   - Non-member pays configured shipping cost
 *   - Expired plan → no discount, pays shipping
 *   - order total math: subtotal + shipping − membership_discount
 *   - earlyAccess propagated from plan to MembershipDiscountResult
 */
class BenefitEnforcementCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

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

    private function seedLicense(): void
    {
        License::firstOrCreate(
            ['license_key' => 'P3-ENFOR-TEST'],
            [
                'domain'            => '',
                'expires_at'        => now()->addYear(),
                'modules'           => ['shop', 'subscription', 'coupon'],
                'platform_valid'    => true,
                'last_validated_at' => now(),
            ]
        );
    }

    private function makeUser(): User
    {
        self::$seq++;
        return User::query()->create([
            'name'     => 'کاربر ' . self::$seq,
            'mobile'   => '093' . str_pad(self::$seq, 8, '0', STR_PAD_LEFT),
            'password' => bcrypt('pass'),
            'is_admin' => false,
        ]);
    }

    private function makeProduct(int $price = 100000): Product
    {
        return Product::query()->create([
            'title'           => 'محصول ' . uniqid(),
            'slug'            => 'prod-' . uniqid(),
            'price'           => $price,
            'status'          => Product::STATUS_PUBLISHED,
            'stock'           => 999,
            'allow_backorder' => true,
        ]);
    }

    private function makePlan(array $attrs = []): SubscriptionPlan
    {
        return SubscriptionPlan::create(array_merge([
            'name'                     => 'پلن تست',
            'slug'                     => 'plan-' . uniqid(),
            'price'                    => 200000,
            'duration'                 => 'month',
            'duration_count'           => 1,
            'is_active'                => true,
            'product_discount_percent' => 0,
            'apply_to_all_products'    => false,
            'free_shipping'            => false,
            'vip_support'              => false,
            'early_access'             => false,
            'loyalty_multiplier'       => 1,
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
            'customer_name'    => 'رضا تست',
            'customer_mobile'  => '09121111111',
            'customer_address' => 'تهران، تست',
        ], $extra);
    }

    // ── tests ─────────────────────────────────────────────────────────────────

    public function test_product_discount_enforced_in_checkout(): void
    {
        $this->seedLicense();
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
        $this->assertEquals('پلن تست', $order->membership_plan_name);
    }

    public function test_free_shipping_sets_shipping_cost_to_zero_in_order(): void
    {
        $this->seedLicense();
        config(['site.shop_shipping_cost' => 30000]);

        $user    = $this->makeUser();
        $product = $this->makeProduct(100000);
        $plan    = $this->makePlan(['free_shipping' => true]);
        $this->activatePlan($user, $plan);

        $this->actingAs($user)
            ->withSession(['cart' => $this->cartSession($product)])
            ->post(route('shop.checkout'), $this->checkoutPayload());

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertEquals(0, (float) $order->shipping_cost);
        $this->assertEquals(100000, (float) $order->total); // no discount, no shipping
    }

    public function test_non_member_pays_configured_shipping_cost(): void
    {
        $this->seedLicense();
        config(['site.shop_shipping_cost' => 25000]);

        $user    = $this->makeUser();
        $product = $this->makeProduct(100000);
        // کاربر بدون اشتراک

        $this->actingAs($user)
            ->withSession(['cart' => $this->cartSession($product)])
            ->post(route('shop.checkout'), $this->checkoutPayload());

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertEquals(25000, (float) $order->shipping_cost);
        $this->assertEquals(125000, (float) $order->total); // 100,000 + 25,000 shipping
    }

    public function test_discount_and_free_shipping_stack_correctly(): void
    {
        $this->seedLicense();
        config(['site.shop_shipping_cost' => 20000]);

        $user    = $this->makeUser();
        $product = $this->makeProduct(200000);
        $plan    = $this->makePlan([
            'product_discount_percent' => 15,
            'apply_to_all_products'    => true,
            'free_shipping'            => true,
        ]);
        $this->activatePlan($user, $plan);

        $this->actingAs($user)
            ->withSession(['cart' => $this->cartSession($product)])
            ->post(route('shop.checkout'), $this->checkoutPayload());

        $order = Order::first();
        $this->assertNotNull($order);
        // subtotal=200,000 | discount=30,000 (15%) | shipping=0 (free)
        // total = 200,000 + 0 − 30,000 = 170,000
        $this->assertEquals(30000, (float) $order->membership_discount_amount);
        $this->assertEquals(0, (float) $order->shipping_cost);
        $this->assertEquals(170000, (float) $order->total);
    }

    public function test_expired_plan_gives_no_discount_and_pays_shipping(): void
    {
        $this->seedLicense();
        config(['site.shop_shipping_cost' => 15000]);

        $user    = $this->makeUser();
        $product = $this->makeProduct(100000);
        $plan    = $this->makePlan([
            'product_discount_percent' => 20,
            'apply_to_all_products'    => true,
            'free_shipping'            => true,
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
        $this->assertEquals(15000, (float) $order->shipping_cost);
        $this->assertEquals(115000, (float) $order->total); // 100,000 + 15,000
    }

    public function test_order_total_math_with_shipping_and_discount(): void
    {
        $this->seedLicense();
        config(['site.shop_shipping_cost' => 10000]);

        $user    = $this->makeUser();
        $product = $this->makeProduct(300000);
        $plan    = $this->makePlan([
            'product_discount_percent' => 10,
            'apply_to_all_products'    => true,
            'free_shipping'            => false,
        ]);
        $this->activatePlan($user, $plan);

        $this->actingAs($user)
            ->withSession(['cart' => $this->cartSession($product)])
            ->post(route('shop.checkout'), $this->checkoutPayload());

        $order = Order::first();
        $this->assertNotNull($order);
        // subtotal=300,000 | shipping=10,000 | discount=30,000 (10%)
        // total = 300,000 + 10,000 − 30,000 = 280,000
        $this->assertEquals(10000, (float) $order->shipping_cost);
        $this->assertEquals(30000, (float) $order->membership_discount_amount);
        $this->assertEquals(280000, (float) $order->total);
    }

    public function test_early_access_badge_shown_in_cart(): void
    {
        $this->seedLicense();
        $user    = $this->makeUser();
        $product = $this->makeProduct(50000);
        $plan    = $this->makePlan(['early_access' => true]);
        $this->activatePlan($user, $plan);

        $response = $this->actingAs($user)
            ->withSession(['cart' => $this->cartSession($product)])
            ->get(route('shop.cart'));

        $response->assertStatus(200);
        $response->assertSee('دسترسی زودهنگام');

        $membership = $response->viewData('membership');
        $this->assertTrue($membership->earlyAccess);
        $this->assertTrue($membership->hasBenefit());
    }

    public function test_vip_support_badge_shown_in_cart(): void
    {
        $this->seedLicense();
        $user    = $this->makeUser();
        $product = $this->makeProduct(50000);
        $plan    = $this->makePlan(['vip_support' => true]);
        $this->activatePlan($user, $plan);

        $response = $this->actingAs($user)
            ->withSession(['cart' => $this->cartSession($product)])
            ->get(route('shop.cart'));

        $response->assertStatus(200);
        $response->assertSee('پشتیبانی VIP');

        $membership = $response->viewData('membership');
        $this->assertTrue($membership->vipSupport);
    }

    public function test_cart_shows_actual_shipping_cost_for_non_member(): void
    {
        $this->seedLicense();
        config(['site.shop_shipping_cost' => 20000]);

        $user    = $this->makeUser();
        $product = $this->makeProduct(80000);

        $response = $this->actingAs($user)
            ->withSession(['cart' => $this->cartSession($product)])
            ->get(route('shop.cart'));

        $response->assertStatus(200);
        $response->assertViewHas('shippingCost', 20000);
    }

    public function test_cart_shows_free_shipping_for_member_with_free_shipping_plan(): void
    {
        $this->seedLicense();
        config(['site.shop_shipping_cost' => 20000]);

        $user    = $this->makeUser();
        $product = $this->makeProduct(80000);
        $plan    = $this->makePlan(['free_shipping' => true]);
        $this->activatePlan($user, $plan);

        $response = $this->actingAs($user)
            ->withSession(['cart' => $this->cartSession($product)])
            ->get(route('shop.cart'));

        $response->assertStatus(200);
        $response->assertViewHas('shippingCost', 0);
        $response->assertSee('رایگان با اشتراک');
    }
}

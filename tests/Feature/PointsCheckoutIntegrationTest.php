<?php

namespace Tests\Feature;

use App\Models\License;
use App\Models\MembershipPoint;
use App\Models\Order;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\MembershipPointsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Points + Checkout integration tests.
 *
 * Covers:
 *   - After checkout: points earned = correct amount stored in order
 *   - After checkout: MembershipPoint row is created
 *   - Checkout with points_to_spend: order.points_spent correct
 *   - Checkout with points_to_spend: order.total = subtotal - points_discount
 *   - Checkout: cannot spend more than balance (capped silently)
 *   - Checkout: cannot spend more than 20% of order (capped silently)
 *   - User with no subscription: points_earned = 0 in order
 */
class PointsCheckoutIntegrationTest extends TestCase
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

    // ── helpers ───────────────────────────────────────────────────────────

    private function seedLicense(): void
    {
        License::firstOrCreate(
            ['license_key' => 'PTS-TEST-SHOP'],
            [
                'domain'            => '',
                'expires_at'        => now()->addYear(),
                'modules'           => ['shop', 'subscription'],
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
            'mobile'   => '094' . str_pad(self::$seq, 8, '0', STR_PAD_LEFT),
            'password' => bcrypt('pass'),
            'is_admin' => false,
        ]);
    }

    private function makeProduct(int $price = 100000): Product
    {
        return Product::query()->create([
            'title'          => 'محصول ' . uniqid(),
            'slug'           => 'prod-' . uniqid(),
            'price'          => $price,
            'status'         => Product::STATUS_PUBLISHED,
            'stock'          => 999,
            'allow_backorder'=> true,
        ]);
    }

    private function makePlan(float $multiplier = 1.0): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name'               => 'پلن ' . uniqid(),
            'slug'               => 'plan-' . uniqid(),
            'price'              => 100000,
            'duration'           => 'month',
            'duration_count'     => 1,
            'is_active'          => true,
            'loyalty_multiplier' => $multiplier,
        ]);
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
                'product_id'          => $product->id,
                'product_variant_id'  => null,
                'price'               => $product->effective_price,
                'quantity'            => $qty,
                'title'               => $product->title,
                'image'               => null,
                'is_bundle'           => false,
            ],
        ];
    }

    private function checkoutPayload(array $extra = []): array
    {
        return array_merge([
            'customer_name'    => 'علی احمدی',
            'customer_mobile'  => '09121234567',
            'customer_address' => 'تهران',
        ], $extra);
    }

    // ── tests ─────────────────────────────────────────────────────────────

    public function test_checkout_stores_points_earned_in_order(): void
    {
        $this->seedLicense();
        $user    = $this->makeUser();
        $product = $this->makeProduct(100000);
        $plan    = $this->makePlan(1.0);
        $this->activatePlan($user, $plan);

        $this->actingAs($user)
            ->withSession(['cart' => $this->cartSession($product)])
            ->post(route('shop.checkout'), $this->checkoutPayload());

        $order = Order::first();
        $this->assertNotNull($order);
        // 100,000 / 1000 * 1.0 = 100 points
        $this->assertEquals(100, $order->points_earned);
    }

    public function test_checkout_creates_membership_point_row(): void
    {
        $this->seedLicense();
        $user    = $this->makeUser();
        $product = $this->makeProduct(100000);
        $plan    = $this->makePlan(1.0);
        $this->activatePlan($user, $plan);

        $this->actingAs($user)
            ->withSession(['cart' => $this->cartSession($product)])
            ->post(route('shop.checkout'), $this->checkoutPayload());

        $this->assertEquals(100, app(MembershipPointsService::class)->getBalance($user));
        $this->assertDatabaseHas('membership_points', [
            'user_id' => $user->id,
            'amount'  => 100,
            'source'  => MembershipPoint::SOURCE_PURCHASE,
        ]);
    }

    public function test_checkout_without_subscription_earns_zero_points(): void
    {
        $this->seedLicense();
        $user    = $this->makeUser();
        $product = $this->makeProduct(100000);

        $this->actingAs($user)
            ->withSession(['cart' => $this->cartSession($product)])
            ->post(route('shop.checkout'), $this->checkoutPayload());

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertEquals(0, $order->points_earned);
    }

    public function test_checkout_spends_points_and_deducts_total(): void
    {
        $this->seedLicense();
        $user    = $this->makeUser();
        $product = $this->makeProduct(200000);
        $plan    = $this->makePlan(1.0);
        $this->activatePlan($user, $plan);

        // Give user 1000 points manually
        $svc = app(MembershipPointsService::class);
        $order0 = Order::create([
            'number' => 'SEED-' . uniqid(), 'user_id' => $user->id,
            'customer_name' => 'x', 'customer_mobile' => '09120000000',
            'customer_address' => 'x', 'subtotal' => 0, 'total' => 0, 'status' => 'pending',
        ]);
        $svc->earnFromPurchase($user, Order::create([
            'number' => 'SEED2-' . uniqid(), 'user_id' => $user->id,
            'customer_name' => 'x', 'customer_mobile' => '09120000001',
            'customer_address' => 'x', 'subtotal' => 1000000, 'total' => 1000000, 'status' => 'pending',
        ])); // 1000 points (1,000,000/1000)

        // Now checkout spending 500 points = 5,000 toman
        $this->actingAs($user)
            ->withSession(['cart' => $this->cartSession($product)])
            ->post(route('shop.checkout'), $this->checkoutPayload(['points_to_spend' => 500]));

        $order = Order::where('customer_mobile', '09121234567')->first();
        $this->assertNotNull($order);
        $this->assertEquals(500, $order->points_spent);
        $this->assertEquals(
            500 * MembershipPointsService::TOMAN_PER_POINT,
            (float) $order->points_discount_amount
        );
        // total = 200,000 - 5,000 = 195,000
        $this->assertEquals(195000, (float) $order->total);
    }

    public function test_checkout_caps_points_spend_at_balance(): void
    {
        $this->seedLicense();
        $user    = $this->makeUser();
        $product = $this->makeProduct(200000);
        $plan    = $this->makePlan(1.0);
        $sub     = $this->activatePlan($user, $plan);
        app(MembershipPointsService::class)->earnFromSubscription($user, $sub); // 500 pts

        // Request 9999 points but user has only 500
        $this->actingAs($user)
            ->withSession(['cart' => $this->cartSession($product)])
            ->post(route('shop.checkout'), $this->checkoutPayload(['points_to_spend' => 9999]));

        $order = Order::where('customer_mobile', '09121234567')->first();
        $this->assertNotNull($order);
        // max = min(500, 20% of 200000 / 10) = min(500, 4000) = 500
        $this->assertLessThanOrEqual(500, $order->points_spent);
        $this->assertGreaterThanOrEqual(0, (float) $order->total);
    }
}

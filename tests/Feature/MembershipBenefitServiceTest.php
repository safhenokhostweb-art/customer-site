<?php

namespace Tests\Feature;

use App\Dto\MembershipDiscountResult;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\MembershipBenefitService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * MembershipBenefitService unit tests.
 *
 * Covers:
 *   - No subscription → empty result
 *   - apply_to_all_products + discount → correct calculation
 *   - Product-scoped discount → only eligible products discounted
 *   - Pivot-level discount_percent override
 *   - free_shipping flag propagated
 *   - vip_support flag propagated
 *   - Discount capped at subtotal (no negative total)
 *   - Cache invalidation
 *   - Guest user (null) → empty result
 */
class MembershipBenefitServiceTest extends TestCase
{
    use RefreshDatabase;

    private MembershipBenefitService $service;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        config(['site.owner_site' => true]);
        $this->service = app(MembershipBenefitService::class);
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    // ── helpers ───────────────────────────────────────────────────────────────

    private function makeUser(): User
    {
        static $seq = 0;
        $seq++;
        return User::query()->create([
            'name'     => 'کاربر تست ' . $seq,
            'mobile'   => '091' . str_pad($seq, 8, '0', STR_PAD_LEFT),
            'password' => bcrypt('password'),
            'is_admin' => false,
        ]);
    }

    private function makePlan(array $attrs = []): SubscriptionPlan
    {
        return SubscriptionPlan::create(array_merge([
            'name'                    => 'پلن آزمایشی',
            'slug'                    => 'test-' . uniqid(),
            'price'                   => 100000,
            'duration'                => 'month',
            'duration_count'          => 1,
            'is_active'               => true,
            'product_discount_percent'=> 0,
            'apply_to_all_products'   => false,
            'free_shipping'           => false,
            'vip_support'             => false,
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

    private function makeCart(array $lines): array
    {
        $cart = [];
        foreach ($lines as [$productId, $price, $qty]) {
            $key = $productId . ':0';
            $cart[$key] = [
                'product_id'         => $productId,
                'product_variant_id' => null,
                'price'              => $price,
                'quantity'           => $qty,
                'title'              => 'محصول ' . $productId,
            ];
        }
        return $cart;
    }

    // ── tests ─────────────────────────────────────────────────────────────────

    public function test_guest_user_returns_empty(): void
    {
        $result = $this->service->calculate(null, [], 100000);

        $this->assertFalse($result->hasBenefit());
        $this->assertEquals(0, $result->discountAmount);
        $this->assertNull($result->plan);
    }

    public function test_user_without_subscription_returns_empty(): void
    {
        $user   = $this->makeUser();
        $cart   = $this->makeCart([[1, 50000, 2]]);
        $result = $this->service->calculate($user, $cart, 100000);

        $this->assertFalse($result->hasBenefit());
        $this->assertEquals(0, $result->discountAmount);
    }

    public function test_expired_subscription_returns_empty(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan(['product_discount_percent' => 10, 'apply_to_all_products' => true]);

        Subscription::create([
            'user_id'              => $user->id,
            'subscription_plan_id' => $plan->id,
            'status'               => Subscription::STATUS_EXPIRED,
            'starts_at'            => now()->subDays(40),
            'ends_at'              => now()->subDays(10),
        ]);

        $result = $this->service->calculate($user, $this->makeCart([[1, 100000, 1]]), 100000);
        $this->assertEquals(0, $result->discountAmount);
    }

    public function test_apply_to_all_products_calculates_correct_discount(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan([
            'product_discount_percent' => 15,
            'apply_to_all_products'    => true,
        ]);
        $this->activatePlan($user, $plan);

        // subtotal = 200,000
        $cart   = $this->makeCart([[1, 100000, 1], [2, 50000, 2]]);
        $result = $this->service->calculate($user, $cart, 200000);

        $this->assertTrue($result->hasDiscount());
        $this->assertEquals(30000, $result->discountAmount); // 15% of 200,000
        $this->assertTrue($result->appliedToAll);
        $this->assertEquals('پلن آزمایشی', $result->planName);
    }

    public function test_product_scoped_discount_only_applies_to_linked_products(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan([
            'product_discount_percent' => 20,
            'apply_to_all_products'    => false,
        ]);
        $this->activatePlan($user, $plan);

        // Create real products and link product 10 to plan
        $p10 = Product::query()->create(['title' => 'P10', 'slug' => 'p10-' . uniqid(), 'price' => 100000, 'stock' => 999, 'status' => Product::STATUS_PUBLISHED]);
        $p20 = Product::query()->create(['title' => 'P20', 'slug' => 'p20-' . uniqid(), 'price' => 100000, 'stock' => 999, 'status' => Product::STATUS_PUBLISHED]);
        $plan->products()->attach($p10->id, ['discount_percent' => 0]);

        Cache::flush();

        $cart = $this->makeCart([
            [$p10->id, 100000, 1],
            [$p20->id, 100000, 1],
        ]);
        $result = $this->service->calculate($user, $cart, 200000);

        // Only p10 gets 20% discount = 20,000
        $this->assertEquals(20000, $result->discountAmount);
        $this->assertContains($p10->id, $result->appliedProductIds);
        $this->assertNotContains($p20->id, $result->appliedProductIds);
    }

    public function test_pivot_discount_percent_overrides_plan_default(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan([
            'product_discount_percent' => 10,
            'apply_to_all_products'    => false,
        ]);
        $this->activatePlan($user, $plan);

        $product = Product::query()->create(['title' => 'Pivot Test', 'slug' => 'pivot-' . uniqid(), 'price' => 100000, 'stock' => 999, 'status' => Product::STATUS_PUBLISHED]);
        $plan->products()->attach($product->id, ['discount_percent' => 30]); // override to 30%

        Cache::flush();

        $cart   = $this->makeCart([[$product->id, 100000, 1]]);
        $result = $this->service->calculate($user, $cart, 100000);

        $this->assertEquals(30000, $result->discountAmount); // pivot 30%, not plan 10%
    }

    public function test_free_shipping_flag_propagated(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan(['free_shipping' => true]);
        $this->activatePlan($user, $plan);

        $result = $this->service->calculate($user, [], 0);

        $this->assertTrue($result->freeShipping);
        $this->assertTrue($result->hasBenefit());
    }

    public function test_vip_support_flag_propagated(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan(['vip_support' => true]);
        $this->activatePlan($user, $plan);

        $result = $this->service->calculate($user, [], 0);

        $this->assertTrue($result->vipSupport);
        $this->assertTrue($result->hasBenefit());
    }

    public function test_discount_capped_at_subtotal(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan([
            'product_discount_percent' => 100,
            'apply_to_all_products'    => true,
        ]);
        $this->activatePlan($user, $plan);

        $result = $this->service->calculate($user, $this->makeCart([[1, 50000, 1]]), 50000);

        // 100% discount → capped at subtotal
        $this->assertEquals(50000, $result->discountAmount);
    }

    public function test_empty_cart_with_apply_all_gives_zero_discount(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan([
            'product_discount_percent' => 15,
            'apply_to_all_products'    => true,
        ]);
        $this->activatePlan($user, $plan);

        $result = $this->service->calculate($user, [], 0);

        $this->assertEquals(0, $result->discountAmount);
    }

    public function test_cache_invalidation_refreshes_plan(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan([
            'product_discount_percent' => 10,
            'apply_to_all_products'    => true,
        ]);
        $this->activatePlan($user, $plan);

        // Prime cache
        $this->service->calculate($user, $this->makeCart([[1, 100000, 1]]), 100000);

        // Invalidate
        $this->service->invalidateCache($user);

        // Plan cancelled
        Subscription::where('user_id', $user->id)->update(['status' => Subscription::STATUS_CANCELLED]);

        $result = $this->service->calculate($user, $this->makeCart([[1, 100000, 1]]), 100000);

        $this->assertEquals(0, $result->discountAmount);
    }
}

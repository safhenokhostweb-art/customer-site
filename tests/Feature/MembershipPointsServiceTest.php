<?php

namespace Tests\Feature;

use App\Models\MembershipPoint;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\MembershipBenefitService;
use App\Services\MembershipPointsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * MembershipPointsService unit tests.
 *
 * Covers:
 *   - Balance = 0 for new user
 *   - earnFromPurchase: correct amount with loyalty_multiplier
 *   - earnFromPurchase: zero when no active plan
 *   - earnFromSubscription: POINTS_ON_SUBSCRIBE fixed
 *   - earnFromRenewal: POINTS_ON_RENEW fixed
 *   - getBalance: sums all non-expired rows
 *   - canSpend: true when balance sufficient
 *   - canSpend: false when balance insufficient
 *   - maxSpendablePoints: capped at 20% of order
 *   - maxSpendablePoints: capped at user balance
 *   - pointsToToman: correct conversion
 *   - spendAtCheckout: deducts balance, throws on insufficient
 *   - balance_after snapshot is correct
 */
class MembershipPointsServiceTest extends TestCase
{
    use RefreshDatabase;

    private MembershipPointsService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        config(['site.owner_site' => true]);
        Cache::flush();
        $this->svc = app(MembershipPointsService::class);
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    // ── helpers ───────────────────────────────────────────────────────────

    private static int $seq = 0;

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

    private function makePlan(float $multiplier = 1.0): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name'              => 'پلن ' . uniqid(),
            'slug'              => 'plan-' . uniqid(),
            'price'             => 100000,
            'duration'          => 'month',
            'duration_count'    => 1,
            'is_active'         => true,
            'loyalty_multiplier'=> $multiplier,
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

    private function makeOrder(User $user, int $subtotal): Order
    {
        return Order::create([
            'number'           => 'TEST-' . uniqid(),
            'user_id'          => $user->id,
            'customer_name'    => $user->name,
            'customer_mobile'  => $user->mobile,
            'customer_address' => 'آدرس',
            'subtotal'         => $subtotal,
            'total'            => $subtotal,
            'status'           => 'pending',
        ]);
    }

    // ── tests ─────────────────────────────────────────────────────────────

    public function test_new_user_has_zero_balance(): void
    {
        $user = $this->makeUser();
        $this->assertEquals(0, $this->svc->getBalance($user));
    }

    public function test_earn_from_purchase_with_multiplier_1(): void
    {
        $user  = $this->makeUser();
        $plan  = $this->makePlan(1.0);
        $this->activatePlan($user, $plan);
        $order = $this->makeOrder($user, 100000);

        $this->svc->earnFromPurchase($user, $order);

        // 100,000 / 1000 * 1.0 = 100 points
        $this->assertEquals(100, $this->svc->getBalance($user));
    }

    public function test_earn_from_purchase_with_multiplier_1_5(): void
    {
        $user  = $this->makeUser();
        $plan  = $this->makePlan(1.5);
        $this->activatePlan($user, $plan);
        // Invalidate plan cache to ensure the new plan is picked up
        app(\App\Services\MembershipBenefitService::class)->invalidateCache($user);
        $order = $this->makeOrder($user, 100000);

        $before = $this->svc->getBalance($user);
        $this->svc->earnFromPurchase($user, $order);
        $earned = $this->svc->getBalance($user) - $before;

        // 100,000 / 1000 * 1.5 = 150 points
        $this->assertEquals(150, $earned);
    }

    public function test_earn_from_purchase_zero_without_plan(): void
    {
        $user  = $this->makeUser(); // no subscription
        $order = $this->makeOrder($user, 100000);

        $result = $this->svc->earnFromPurchase($user, $order);

        $this->assertNull($result);
        $this->assertEquals(0, $this->svc->getBalance($user));
    }

    public function test_earn_from_subscription_adds_fixed_points(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan();
        $sub  = $this->activatePlan($user, $plan);

        $this->svc->earnFromSubscription($user, $sub);

        $this->assertEquals(MembershipPointsService::POINTS_ON_SUBSCRIBE, $this->svc->getBalance($user));
    }

    public function test_earn_from_renewal_adds_fixed_points(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan();
        $sub  = $this->activatePlan($user, $plan);

        $this->svc->earnFromRenewal($user, $sub);

        $this->assertEquals(MembershipPointsService::POINTS_ON_RENEW, $this->svc->getBalance($user));
    }

    public function test_balance_accumulates_multiple_transactions(): void
    {
        $user  = $this->makeUser();
        $plan  = $this->makePlan(1.0);
        $sub   = $this->activatePlan($user, $plan);
        $order = $this->makeOrder($user, 200000);

        $this->svc->earnFromSubscription($user, $sub);
        $this->svc->earnFromPurchase($user, $order);

        // 500 (subscribe) + 200 (purchase: 200,000/1000) = 700
        $this->assertEquals(
            MembershipPointsService::POINTS_ON_SUBSCRIBE + 200,
            $this->svc->getBalance($user)
        );
    }

    public function test_can_spend_true_when_sufficient(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan();
        $sub  = $this->activatePlan($user, $plan);
        $this->svc->earnFromSubscription($user, $sub); // 500 pts

        $this->assertTrue($this->svc->canSpend($user, 300));
    }

    public function test_can_spend_false_when_insufficient(): void
    {
        $user = $this->makeUser();
        $this->assertFalse($this->svc->canSpend($user, 100));
    }

    public function test_max_spendable_capped_at_20_percent(): void
    {
        $user  = $this->makeUser();
        $plan  = $this->makePlan();
        $sub   = $this->activatePlan($user, $plan);
        $this->svc->earnFromSubscription($user, $sub); // 500 pts

        // order = 100,000 toman → max 20% = 20,000 toman → 2000 pts
        // user has only 500 pts, so max = min(500, 2000) = 500
        $max = $this->svc->maxSpendablePoints($user, 100000);
        $this->assertEquals(500, $max);
    }

    public function test_max_spendable_capped_at_balance(): void
    {
        $user  = $this->makeUser();
        $plan  = $this->makePlan(10.0); // high multiplier
        $sub   = $this->activatePlan($user, $plan);
        $order = $this->makeOrder($user, 1000000);
        $this->svc->earnFromPurchase($user, $order); // 1000*10 = 10,000 pts

        // order = 50,000 toman → max 20% = 10,000 toman = 1000 pts
        // user has 10,000 pts, so max = min(10000, 1000) = 1000
        $max = $this->svc->maxSpendablePoints($user, 50000);
        $this->assertEquals(1000, $max);
    }

    public function test_points_to_toman_conversion(): void
    {
        $this->assertEquals(
            1000 * MembershipPointsService::TOMAN_PER_POINT,
            $this->svc->pointsToToman(1000)
        );
    }

    public function test_spend_at_checkout_deducts_balance(): void
    {
        $user  = $this->makeUser();
        $plan  = $this->makePlan();
        $sub   = $this->activatePlan($user, $plan);
        $this->svc->earnFromSubscription($user, $sub); // 500 pts

        $order = $this->makeOrder($user, 100000);
        $this->svc->spendAtCheckout($user, 200, $order);

        $this->assertEquals(300, $this->svc->getBalance($user)); // 500 - 200
    }

    public function test_spend_throws_when_insufficient(): void
    {
        $user  = $this->makeUser();
        $order = $this->makeOrder($user, 100000);

        $this->expectException(\RuntimeException::class);
        $this->svc->spendAtCheckout($user, 100, $order);
    }

    public function test_balance_after_snapshot_is_correct(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan();
        $sub  = $this->activatePlan($user, $plan);
        $this->svc->earnFromSubscription($user, $sub); // 500 pts

        $point = MembershipPoint::where('user_id', $user->id)->latest()->first();
        $this->assertEquals(500, $point->balance_after);
    }
}

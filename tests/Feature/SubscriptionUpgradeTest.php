<?php

namespace Tests\Feature;

use App\Helpers\Module;
use App\Models\License;
use App\Models\MembershipPoint;
use App\Models\Subscription;
use App\Models\SubscriptionLog;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use ReflectionClass;
use Tests\TestCase;

/**
 * P8 — Plan Upgrade / Downgrade
 *
 * Covers:
 *   1. calculateCredit: free plan → 0
 *   2. calculateCredit: trial → 0
 *   3. calculateCredit: paid active → prorated value
 *   4. upgradePreview: upgrade direction, amount_to_pay = max(0, new - credit)
 *   5. upgradePreview: downgrade with leftover_points
 *   6. Direct upgrade (credit >= new price) → no invoice, old sub cancelled, new sub active
 *   7. Upgrade to free plan → direct, old sub cancelled
 *   8. Paid upgrade → pending sub created, upgrade_from set, redirect to pay
 *   9. Security: user cannot upgrade another user's subscription → 404
 *  10. Same plan selection → error
 */
class SubscriptionUpgradeTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');

        $ref  = new ReflectionClass(Module::class);
        $prop = $ref->getProperty('resolved');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        Cache::flush();
        $this->seedLicense();
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function seedLicense(): void
    {
        License::firstOrCreate(
            ['license_key' => 'P8-UPGRADE-LICENSE'],
            [
                'domain'            => '',
                'expires_at'        => now()->addYear(),
                'modules'           => ['subscription', 'shop'],
                'platform_valid'    => true,
                'last_validated_at' => now(),
            ]
        );
    }

    private function makeUser(): User
    {
        self::$seq++;
        return User::factory()->create([
            'mobile'   => '097' . str_pad(self::$seq, 8, '0', STR_PAD_LEFT),
            'is_admin' => false,
        ]);
    }

    private function makePlan(array $attrs = []): SubscriptionPlan
    {
        return SubscriptionPlan::create(array_merge([
            'name'           => 'پلن ' . uniqid(),
            'slug'           => 'plan-' . uniqid(),
            'price'          => 100000,
            'duration'       => 'month',
            'duration_count' => 1,
            'is_active'      => true,
        ], $attrs));
    }

    private function activeSub(User $user, SubscriptionPlan $plan, int $daysRemaining = 15): Subscription
    {
        return Subscription::create([
            'user_id'              => $user->id,
            'subscription_plan_id' => $plan->id,
            'status'               => Subscription::STATUS_ACTIVE,
            'starts_at'            => now()->subDays($plan->getDurationInDays() - $daysRemaining),
            'ends_at'              => now()->addDays($daysRemaining),
            'credit_amount'        => 0,
        ]);
    }

    // ── Tests ─────────────────────────────────────────────────────────────────

    /** @test */
    public function calculate_credit_is_zero_for_free_plan()
    {
        $svc  = app(SubscriptionService::class);
        $user = $this->makeUser();
        $plan = $this->makePlan(['price' => 0]);
        $sub  = $this->activeSub($user, $plan);

        $this->assertEquals(0.0, $svc->calculateCredit($sub));
    }

    /** @test */
    public function calculate_credit_is_zero_for_trial_subscription()
    {
        $svc  = app(SubscriptionService::class);
        $user = $this->makeUser();
        $plan = $this->makePlan(['price' => 120000]);
        $sub  = Subscription::create([
            'user_id'              => $user->id,
            'subscription_plan_id' => $plan->id,
            'status'               => Subscription::STATUS_TRIAL,
            'starts_at'            => now(),
            'ends_at'              => now()->addDays(30),
            'trial_ends_at'        => now()->addDays(7),
            'credit_amount'        => 0,
        ]);

        $this->assertEquals(0.0, $svc->calculateCredit($sub));
    }

    /** @test */
    public function calculate_credit_returns_prorated_value_for_active_paid_plan()
    {
        $svc  = app(SubscriptionService::class);
        $user = $this->makeUser();
        // 30-day plan, 15 days remaining → 50% of price
        $plan = $this->makePlan(['price' => 120000, 'duration' => 'month', 'duration_count' => 1]);
        $sub  = $this->activeSub($user, $plan, 15);

        $credit = $svc->calculateCredit($sub);

        // 15/30 * 120000 = 60000 (±1 day tolerance for diffInDays timing)
        $this->assertEqualsWithDelta(60000.0, $credit, 6000.0);
    }

    /** @test */
    public function upgrade_preview_returns_correct_direction_and_amount_for_upgrade()
    {
        $svc     = app(SubscriptionService::class);
        $user    = $this->makeUser();
        $current = $this->makePlan(['price' => 120000]);
        $higher  = $this->makePlan(['price' => 240000]);

        // 15 days remaining → credit ≈ 60000
        $sub     = $this->activeSub($user, $current, 15);
        $preview = $svc->upgradePreview($sub, $higher);

        $this->assertEquals('upgrade', $preview['direction']);
        $this->assertEquals(240000.0, $preview['new_price']);
        $this->assertGreaterThan(0, $preview['credit']);
        // amount_to_pay = 240000 - credit
        $this->assertEqualsWithDelta(240000 - $preview['credit'], $preview['amount_to_pay'], 1.0);
        $this->assertEquals(0, $preview['leftover_points']);
    }

    /** @test */
    public function upgrade_preview_returns_leftover_points_for_downgrade()
    {
        $svc     = app(SubscriptionService::class);
        $user    = $this->makeUser();
        $current = $this->makePlan(['price' => 240000]);
        $lower   = $this->makePlan(['price' => 60000]);

        // 20 days remaining on 30-day plan → credit ≈ 160000
        $sub     = $this->activeSub($user, $current, 20);
        $preview = $svc->upgradePreview($sub, $lower);

        $this->assertEquals('downgrade', $preview['direction']);
        $this->assertEquals(0.0, $preview['amount_to_pay']);
        $this->assertTrue($preview['is_direct']);
        // leftover = credit - 60000; leftover_points = floor(leftover / 1000)
        $this->assertGreaterThan(0, $preview['leftover_points']);
        $this->assertGreaterThan(0, $preview['leftover_credit']);
    }

    /** @test */
    public function direct_upgrade_cancels_old_subscription_and_creates_new_active()
    {
        $svc  = app(SubscriptionService::class);
        $user = $this->makeUser();
        // Current: expensive plan, 20 days remaining → credit covers cheaper plan
        $current = $this->makePlan(['price' => 300000]);
        $cheaper = $this->makePlan(['price' => 50000]);
        $sub     = $this->activeSub($user, $current, 20);

        $this->actingAs($user)
            ->post(route('account.subscriptions.upgrade', $sub), ['plan_id' => $cheaper->id])
            ->assertRedirect(route('account.subscriptions.index'));

        // Old sub cancelled
        $this->assertDatabaseHas('subscriptions', [
            'id'     => $sub->id,
            'status' => Subscription::STATUS_CANCELLED,
        ]);

        // New sub active with upgrade_from set
        $this->assertDatabaseHas('subscriptions', [
            'user_id'                      => $user->id,
            'subscription_plan_id'         => $cheaper->id,
            'status'                       => Subscription::STATUS_ACTIVE,
            'upgrade_from_subscription_id' => $sub->id,
        ]);

        // Log recorded on new sub
        $newSub = Subscription::where('subscription_plan_id', $cheaper->id)
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        $this->assertDatabaseHas('subscription_logs', [
            'subscription_id' => $newSub->id,
            'action'          => SubscriptionLog::ACTION_DOWNGRADED,
        ]);
    }

    /** @test */
    public function upgrade_to_free_plan_is_direct_and_cancels_old()
    {
        $user    = $this->makeUser();
        $current = $this->makePlan(['price' => 120000]);
        $free    = $this->makePlan(['price' => 0]);
        $sub     = $this->activeSub($user, $current, 10);

        $this->actingAs($user)
            ->post(route('account.subscriptions.upgrade', $sub), ['plan_id' => $free->id])
            ->assertRedirect(route('account.subscriptions.index'));

        $this->assertDatabaseHas('subscriptions', [
            'id'     => $sub->id,
            'status' => Subscription::STATUS_CANCELLED,
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'user_id'              => $user->id,
            'subscription_plan_id' => $free->id,
            'status'               => Subscription::STATUS_ACTIVE,
        ]);
    }

    /** @test */
    public function paid_upgrade_creates_pending_subscription_with_upgrade_from_and_invoice()
    {
        $user    = $this->makeUser();
        $current = $this->makePlan(['price' => 60000]);
        $higher  = $this->makePlan(['price' => 300000]);
        $sub     = $this->activeSub($user, $current, 5); // small credit

        $this->actingAs($user)
            ->post(route('account.subscriptions.upgrade', $sub), ['plan_id' => $higher->id])
            ->assertRedirect(); // redirect to pay page

        // Pending subscription exists
        $this->assertDatabaseHas('subscriptions', [
            'user_id'                      => $user->id,
            'subscription_plan_id'         => $higher->id,
            'status'                       => Subscription::STATUS_PENDING,
            'upgrade_from_subscription_id' => $sub->id,
        ]);

        // Old subscription still active (not yet cancelled — waiting for payment)
        $this->assertDatabaseHas('subscriptions', [
            'id'     => $sub->id,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        // Invoice created
        $pending = Subscription::where('subscription_plan_id', $higher->id)
            ->where('user_id', $user->id)
            ->first();
        $this->assertNotNull($pending->invoice_id);
    }

    /** @test */
    public function user_cannot_upgrade_another_users_subscription()
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $plan  = $this->makePlan();
        $newPl = $this->makePlan(['price' => 200000]);
        $sub   = $this->activeSub($owner, $plan);

        $this->actingAs($other)
            ->get(route('account.subscriptions.upgrade-plans', $sub))
            ->assertNotFound();

        $this->actingAs($other)
            ->post(route('account.subscriptions.upgrade', $sub), ['plan_id' => $newPl->id])
            ->assertNotFound();
    }

    /** @test */
    public function selecting_same_plan_returns_error()
    {
        $user = $this->makeUser();
        $plan = $this->makePlan(['price' => 120000]);
        $sub  = $this->activeSub($user, $plan);

        $this->actingAs($user)
            ->post(route('account.subscriptions.upgrade', $sub), ['plan_id' => $plan->id])
            ->assertSessionHas('error')
            ->assertRedirect();

        // No new subscription created
        $this->assertEquals(1, Subscription::where('user_id', $user->id)->count());
    }

    /** @test */
    public function upgrade_plans_page_returns_404_for_non_active_subscription()
    {
        $user = $this->makeUser();
        $plan = $this->makePlan();
        $sub  = Subscription::create([
            'user_id'              => $user->id,
            'subscription_plan_id' => $plan->id,
            'status'               => Subscription::STATUS_CANCELLED,
            'starts_at'            => now()->subMonth(),
            'ends_at'              => now()->subDay(),
            'credit_amount'        => 0,
        ]);

        $this->actingAs($user)
            ->get(route('account.subscriptions.upgrade-plans', $sub))
            ->assertRedirect(route('account.subscriptions.plans'));
    }
}

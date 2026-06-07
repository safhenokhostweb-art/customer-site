<?php

namespace Tests\Feature;

use App\Helpers\Module;
use App\Models\Coupon;
use App\Models\Invoice;
use App\Models\License;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

/**
 * P6 — Subscription Checkout Flow: subscribe, plans, show, index, security.
 *
 * Covers:
 *   - Guest redirect to login on plans page and subscribe
 *   - Plans page shows active plans only
 *   - Free plan → immediate activation (STATUS_ACTIVE)
 *   - Free plan with trial_days → STATUS_TRIAL
 *   - Paid plan → pending subscription + invoice + redirect to pay
 *   - Paid plan with coupon → discount applied to invoice total
 *   - User with active subscription blocked from re-subscribing
 *   - User with pending subscription blocked from re-subscribing
 *   - Inactive plan rejected
 *   - Show own subscription → 200
 *   - Cannot show another user's subscription → 404
 *   - Index shows only own subscriptions
 *   - Subscribe to non-existent plan → validation error
 */
class SubscriptionCheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');

        // Reset Module resolved cache so license lookup is fresh each test
        $ref  = new ReflectionClass(Module::class);
        $prop = $ref->getProperty('resolved');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        $this->seedLicense();
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
            ['license_key' => 'P6-FLOW-LICENSE'],
            [
                'domain'            => '',
                'expires_at'        => now()->addYear(),
                'modules'           => ['subscription', 'shop', 'coupon'],
                'platform_valid'    => true,
                'last_validated_at' => now(),
            ]
        );
    }

    private function makeUser(): User
    {
        self::$seq++;
        return User::factory()->create([
            'mobile'   => '094' . str_pad(self::$seq, 8, '0', STR_PAD_LEFT),
            'is_admin' => false,
        ]);
    }

    private function makePlan(array $attrs = []): SubscriptionPlan
    {
        return SubscriptionPlan::create(array_merge([
            'name'           => 'پلن تست ' . uniqid(),
            'slug'           => 'plan-' . uniqid(),
            'price'          => 0,
            'duration'       => 'month',
            'duration_count' => 1,
            'is_active'      => true,
            'trial_days'     => 0,
            'order'          => 0,
        ], $attrs));
    }

    private function activateSubscription(User $user, SubscriptionPlan $plan, string $status = Subscription::STATUS_ACTIVE): Subscription
    {
        return Subscription::create([
            'user_id'              => $user->id,
            'subscription_plan_id' => $plan->id,
            'status'               => $status,
            'starts_at'            => now(),
            'ends_at'              => now()->addMonth(),
        ]);
    }

    // ── tests ─────────────────────────────────────────────────────────────────

    public function test_guest_redirected_to_login_on_plans_page(): void
    {
        $response = $this->get(route('account.subscriptions.plans'));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_redirected_to_login_on_subscribe_post(): void
    {
        $plan     = $this->makePlan();
        $response = $this->post(route('account.subscriptions.subscribe'), ['plan_id' => $plan->id]);

        $response->assertRedirect(route('login'));
    }

    public function test_plans_page_shows_active_plans_only(): void
    {
        $user        = $this->makeUser();
        $activePlan  = $this->makePlan(['name' => 'پلن فعال', 'is_active' => true]);
        $inactivePlan = $this->makePlan(['name' => 'پلن غیرفعال', 'is_active' => false]);

        $response = $this->actingAs($user)->get(route('account.subscriptions.plans'));

        $response->assertStatus(200);
        $response->assertSee('پلن فعال');
        $response->assertDontSee('پلن غیرفعال');
    }

    public function test_free_plan_activates_immediately(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan(['price' => 0, 'trial_days' => 0]);

        $response = $this->actingAs($user)
            ->post(route('account.subscriptions.subscribe'), ['plan_id' => $plan->id]);

        $response->assertRedirect(route('account.subscriptions.index'));
        $this->assertDatabaseHas('subscriptions', [
            'user_id'              => $user->id,
            'subscription_plan_id' => $plan->id,
            'status'               => Subscription::STATUS_ACTIVE,
        ]);
    }

    public function test_free_plan_with_trial_days_sets_trial_status(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan(['price' => 0, 'trial_days' => 7]);

        $this->actingAs($user)
            ->post(route('account.subscriptions.subscribe'), ['plan_id' => $plan->id]);

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'status'  => Subscription::STATUS_TRIAL,
        ]);
    }

    public function test_paid_plan_creates_pending_subscription_and_invoice(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan(['price' => 150000]);

        $response = $this->actingAs($user)
            ->post(route('account.subscriptions.subscribe'), ['plan_id' => $plan->id]);

        // ریدایرکت به صفحه پرداخت
        $response->assertRedirect();
        $this->assertStringContainsString('/subscription/pay/', $response->headers->get('Location'));

        // subscription با status pending ایجاد شده
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'status'  => Subscription::STATUS_PENDING,
        ]);

        // invoice با status pending ایجاد شده
        $this->assertDatabaseHas('invoices', [
            'user_id' => $user->id,
            'total'   => 150000,
            'status'  => 'pending',
        ]);
    }

    public function test_paid_plan_with_coupon_discount_applied_to_invoice(): void
    {
        $user   = $this->makeUser();
        $plan   = $this->makePlan(['price' => 200000]);
        $coupon = Coupon::create([
            'code'             => 'SAVE20PCT',
            'discount_type'    => 'percent',
            'discount_percent' => 20,
            'discount_amount'  => null,
            'is_active'        => true,
            'used_count'       => 0,
        ]);

        $this->actingAs($user)->post(route('account.subscriptions.subscribe'), [
            'plan_id'     => $plan->id,
            'coupon_code' => 'SAVE20PCT',
        ]);

        // total باید 200,000 × 0.8 = 160,000 باشد
        $this->assertDatabaseHas('invoices', [
            'user_id' => $user->id,
            'total'   => 160000,
        ]);
    }

    public function test_user_blocked_from_subscribing_when_already_active(): void
    {
        $user      = $this->makeUser();
        $plan      = $this->makePlan(['price' => 0]);
        $otherPlan = $this->makePlan(['price' => 0]);

        // اشتراک فعال
        $this->activateSubscription($user, $plan);

        $response = $this->actingAs($user)
            ->post(route('account.subscriptions.subscribe'), ['plan_id' => $otherPlan->id]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // اشتراک دومی ایجاد نشده
        $this->assertDatabaseCount('subscriptions', 1);
    }

    public function test_user_blocked_from_subscribing_when_pending_exists(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan(['price' => 100000]);

        // اشتراک pending دستی
        Subscription::create([
            'user_id'              => $user->id,
            'subscription_plan_id' => $plan->id,
            'status'               => Subscription::STATUS_PENDING,
            'starts_at'            => now(),
            'ends_at'              => now()->addMonth(),
        ]);

        $response = $this->actingAs($user)
            ->post(route('account.subscriptions.subscribe'), ['plan_id' => $plan->id]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('subscriptions', 1);
    }

    public function test_inactive_plan_rejected(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan(['is_active' => false]);

        $response = $this->actingAs($user)
            ->post(route('account.subscriptions.subscribe'), ['plan_id' => $plan->id]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('subscriptions', ['user_id' => $user->id]);
    }

    public function test_subscribe_to_nonexistent_plan_fails_validation(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)
            ->post(route('account.subscriptions.subscribe'), ['plan_id' => 99999]);

        $response->assertSessionHasErrors('plan_id');
    }

    public function test_user_can_view_own_subscription(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan();
        $sub  = $this->activateSubscription($user, $plan);

        $response = $this->actingAs($user)
            ->get(route('account.subscriptions.show', $sub));

        $response->assertStatus(200);
    }

    public function test_user_cannot_view_other_users_subscription(): void
    {
        $owner  = $this->makeUser();
        $other  = $this->makeUser();
        $plan   = $this->makePlan();
        $sub    = $this->activateSubscription($owner, $plan);

        $response = $this->actingAs($other)
            ->get(route('account.subscriptions.show', $sub));

        $response->assertStatus(404);
    }

    public function test_index_shows_only_own_subscriptions(): void
    {
        $user1 = $this->makeUser();
        $user2 = $this->makeUser();
        $plan  = $this->makePlan(['name' => 'پلن مشترک']);

        $this->activateSubscription($user1, $plan);
        $this->activateSubscription($user2, $plan);

        $response = $this->actingAs($user1)->get(route('account.subscriptions.index'));

        $response->assertStatus(200);
        $response->assertViewHas('subscriptions', function ($subs) use ($user1, $user2) {
            // فقط اشتراک user1 نشان داده می‌شود
            foreach ($subs as $s) {
                if ($s->user_id === $user2->id) {
                    return false;
                }
            }
            return $subs->where('user_id', $user1->id)->count() === 1;
        });
    }
}

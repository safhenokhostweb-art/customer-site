<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Subscription;
use App\Models\SubscriptionLog;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * SendSubscriptionRemindersCommand tests.
 *
 * Covers:
 *   - 7-day reminder: sent to subscription expiring in ~7 days
 *   - 7-day reminder: NOT sent twice (idempotency)
 *   - 7-day reminder: NOT sent to subscription expiring > 8 days
 *   - 1-day reminder: sent to subscription expiring in ~1 day
 *   - 1-day reminder: NOT sent twice (idempotency)
 *   - 1-day reminder: generates renewal coupon (Coupon created)
 *   - 1-day reminder: NOT sent to already expired subscription
 *   - dry-run: no DB changes
 */
class SubscriptionRemindersTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        config(['site.owner_site' => true]);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    // ── helpers ───────────────────────────────────────────────────────────

    private function makeUser(): User
    {
        self::$seq++;
        return User::query()->create([
            'name'     => 'کاربر ' . self::$seq,
            'mobile'   => '095' . str_pad(self::$seq, 8, '0', STR_PAD_LEFT),
            'password' => bcrypt('pass'),
            'is_admin' => false,
        ]);
    }

    private function makePlan(): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name'           => 'پلن ' . uniqid(),
            'slug'           => 'p-' . uniqid(),
            'price'          => 100000,
            'duration'       => 'month',
            'duration_count' => 1,
            'is_active'      => true,
        ]);
    }

    private function makeSubscription(User $user, SubscriptionPlan $plan, string $endsAt): Subscription
    {
        return Subscription::create([
            'user_id'              => $user->id,
            'subscription_plan_id' => $plan->id,
            'status'               => Subscription::STATUS_ACTIVE,
            'starts_at'            => now()->subDays(25),
            'ends_at'              => $endsAt,
        ]);
    }

    // ── tests ─────────────────────────────────────────────────────────────

    public function test_7d_reminder_sent_to_expiring_in_7_days(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan();
        $this->makeSubscription($user, $plan, now()->addDays(7)->toDateTimeString());

        $this->artisan('subscriptions:send-reminders')->assertExitCode(0);

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
        ]);
        $sub = Subscription::where('user_id', $user->id)->first();
        $this->assertNotNull($sub->reminder_7d_sent_at);

        $this->assertDatabaseHas('subscription_logs', [
            'subscription_id' => $sub->id,
            'action'          => SubscriptionLog::ACTION_REMINDER_7D,
        ]);
    }

    public function test_7d_reminder_not_sent_twice(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan();
        $sub  = $this->makeSubscription($user, $plan, now()->addDays(7)->toDateTimeString());
        $sub->update(['reminder_7d_sent_at' => now()->subHour()]);

        $this->artisan('subscriptions:send-reminders')->assertExitCode(0);

        $this->assertEquals(
            0,
            SubscriptionLog::where('subscription_id', $sub->id)
                ->where('action', SubscriptionLog::ACTION_REMINDER_7D)
                ->count()
        );
    }

    public function test_7d_reminder_not_sent_to_far_expiry(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan();
        $this->makeSubscription($user, $plan, now()->addDays(30)->toDateTimeString());

        $this->artisan('subscriptions:send-reminders')->assertExitCode(0);

        $sub = Subscription::where('user_id', $user->id)->first();
        $this->assertNull($sub->reminder_7d_sent_at);
    }

    public function test_1d_reminder_sent_to_expiring_tomorrow(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan();
        $this->makeSubscription($user, $plan, now()->addDay()->toDateTimeString());

        $this->artisan('subscriptions:send-reminders')->assertExitCode(0);

        $sub = Subscription::where('user_id', $user->id)->first();
        $this->assertNotNull($sub->reminder_1d_sent_at);

        $this->assertDatabaseHas('subscription_logs', [
            'subscription_id' => $sub->id,
            'action'          => SubscriptionLog::ACTION_REMINDER_1D,
        ]);
    }

    public function test_1d_reminder_generates_renewal_coupon(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan();
        $this->makeSubscription($user, $plan, now()->addDay()->toDateTimeString());

        $couponCountBefore = Coupon::count();

        $this->artisan('subscriptions:send-reminders')->assertExitCode(0);

        $this->assertGreaterThan($couponCountBefore, Coupon::count());

        $sub = Subscription::where('user_id', $user->id)->first();
        $this->assertDatabaseHas('subscription_logs', [
            'subscription_id' => $sub->id,
            'action'          => SubscriptionLog::ACTION_RENEWAL_COUPON,
        ]);
    }

    public function test_1d_reminder_not_sent_twice(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan();
        $sub  = $this->makeSubscription($user, $plan, now()->addDay()->toDateTimeString());
        $sub->update(['reminder_1d_sent_at' => now()->subHour()]);

        $this->artisan('subscriptions:send-reminders')->assertExitCode(0);

        $this->assertEquals(
            0,
            SubscriptionLog::where('subscription_id', $sub->id)
                ->where('action', SubscriptionLog::ACTION_REMINDER_1D)
                ->count()
        );
    }

    public function test_expired_subscription_not_reminded(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan();
        Subscription::create([
            'user_id'              => $user->id,
            'subscription_plan_id' => $plan->id,
            'status'               => Subscription::STATUS_EXPIRED,
            'starts_at'            => now()->subDays(40),
            'ends_at'              => now()->subDays(3),
        ]);

        $this->artisan('subscriptions:send-reminders')->assertExitCode(0);

        $sub = Subscription::where('user_id', $user->id)->first();
        $this->assertNull($sub->reminder_1d_sent_at);
        $this->assertNull($sub->reminder_7d_sent_at);
    }

    public function test_dry_run_makes_no_db_changes(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan();
        $sub  = $this->makeSubscription($user, $plan, now()->addDays(7)->toDateTimeString());

        $this->artisan('subscriptions:send-reminders', ['--dry-run' => true])->assertExitCode(0);

        $sub->refresh();
        $this->assertNull($sub->reminder_7d_sent_at);
        $this->assertEquals(0, SubscriptionLog::count());
    }
}

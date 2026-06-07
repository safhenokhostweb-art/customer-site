<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\SubscriptionLog;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * ProcessRenewalsCommand — P2 Auto-renew tests.
 *
 * Covers:
 *   - Free plan with auto_renew: renewed directly
 *   - Free plan renewed: status stays active, ends_at extended
 *   - Free plan renewed: ACTION_AUTO_RENEWED logged
 *   - Free plan NOT renewed if auto_renew=false
 *   - Free plan NOT renewed if already renewed today (idempotent)
 *   - Free plan NOT renewed if ends_at > window hours (not due yet)
 *   - Free plan NOT renewed if already expired > 1h ago
 *   - Paid plan with auto_renew: invoice created + subscription NOT activated
 *   - Paid plan: ACTION_AUTO_RENEW_INVOICE logged
 *   - Paid plan: no duplicate invoice same day (idempotent)
 *   - Cancelled subscription: NOT processed
 *   - dry-run: no DB changes
 *   - command exits SUCCESS
 */
class ProcessRenewalsTest extends TestCase
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

    // ─── helpers ──────────────────────────────────────────────────────────────

    private function makeUser(): User
    {
        self::$seq++;
        return User::factory()->create(['mobile' => '0912' . str_pad(self::$seq, 7, '0', STR_PAD_LEFT)]);
    }

    private function makePlan(bool $free = true, array $attrs = []): SubscriptionPlan
    {
        return SubscriptionPlan::create(array_merge([
            'name'           => $free ? 'رایگان' : 'ماهانه',
            'slug'           => 'plan-' . uniqid(),
            'price'          => $free ? 0 : 50000,
            'duration'       => 'month',
            'duration_count' => 1,
            'is_active'      => true,
            'order'          => 0,
            'trial_days'     => 0,
        ], $attrs));
    }

    private function makeSubscription(
        User $user,
        SubscriptionPlan $plan,
        bool $autoRenew,
        string $status,
        \Carbon\Carbon $endsAt,
        ?\Carbon\Carbon $renewedAt = null
    ): Subscription {
        return Subscription::create([
            'user_id'              => $user->id,
            'subscription_plan_id' => $plan->id,
            'status'               => $status,
            'auto_renew'           => $autoRenew,
            'starts_at'            => now()->subMonth(),
            'ends_at'              => $endsAt,
            'renewed_at'           => $renewedAt,
        ]);
    }

    // ─── Free plan tests ──────────────────────────────────────────────────────

    public function test_free_plan_auto_renew_renews_directly(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan(free: true);
        $sub  = $this->makeSubscription($user, $plan, true, Subscription::STATUS_ACTIVE, now()->addHours(12));

        $originalEndsAt = $sub->ends_at->copy();

        $this->artisan('subscriptions:process-renewals')->assertSuccessful();

        $sub->refresh();
        $this->assertEquals(Subscription::STATUS_ACTIVE, $sub->status);
        $this->assertTrue($sub->ends_at->gt($originalEndsAt));
        $this->assertNotNull($sub->renewed_at);
    }

    public function test_free_plan_auto_renewed_log_created(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan(free: true);
        $sub  = $this->makeSubscription($user, $plan, true, Subscription::STATUS_ACTIVE, now()->addHours(12));

        $this->artisan('subscriptions:process-renewals')->assertSuccessful();

        $this->assertDatabaseHas('subscription_logs', [
            'subscription_id' => $sub->id,
            'action'          => SubscriptionLog::ACTION_AUTO_RENEWED,
            'performed_by'    => 'system',
        ]);
    }

    public function test_free_plan_no_auto_renew_skipped(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan(free: true);
        $sub  = $this->makeSubscription($user, $plan, false, Subscription::STATUS_ACTIVE, now()->addHours(12));

        $originalEndsAt = $sub->ends_at->copy();

        $this->artisan('subscriptions:process-renewals')->assertSuccessful();

        $sub->refresh();
        $this->assertEquals($originalEndsAt->toDateString(), $sub->ends_at->toDateString());
    }

    public function test_free_plan_already_renewed_today_is_idempotent(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan(free: true);
        $sub  = $this->makeSubscription($user, $plan, true, Subscription::STATUS_ACTIVE, now()->addHours(12), now());

        $this->artisan('subscriptions:process-renewals')->assertSuccessful();

        // لاگ ACTION_AUTO_RENEWED نباید ایجاد شود
        $this->assertDatabaseMissing('subscription_logs', [
            'subscription_id' => $sub->id,
            'action'          => SubscriptionLog::ACTION_AUTO_RENEWED,
        ]);
    }

    public function test_free_plan_not_due_yet_is_skipped(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan(free: true);
        // ۴۸ ساعت مانده — خارج از پنجره ۲۴ساعته
        $sub = $this->makeSubscription($user, $plan, true, Subscription::STATUS_ACTIVE, now()->addHours(48));

        $originalEndsAt = $sub->ends_at->copy();

        $this->artisan('subscriptions:process-renewals')->assertSuccessful();

        $sub->refresh();
        $this->assertEquals($originalEndsAt->toDateString(), $sub->ends_at->toDateString());
    }

    public function test_free_plan_expired_more_than_1h_ago_skipped(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan(free: true);
        $sub  = $this->makeSubscription($user, $plan, true, Subscription::STATUS_ACTIVE, now()->subHours(2));

        $originalEndsAt = $sub->ends_at->copy();

        $this->artisan('subscriptions:process-renewals')->assertSuccessful();

        $sub->refresh();
        $this->assertEquals($originalEndsAt->toDateString(), $sub->ends_at->toDateString());
    }

    public function test_cancelled_subscription_skipped(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan(free: true);
        $sub  = $this->makeSubscription($user, $plan, true, Subscription::STATUS_CANCELLED, now()->addHours(12));

        $this->artisan('subscriptions:process-renewals')->assertSuccessful();

        $this->assertDatabaseMissing('subscription_logs', [
            'subscription_id' => $sub->id,
            'action'          => SubscriptionLog::ACTION_AUTO_RENEWED,
        ]);
    }

    // ─── Paid plan tests ──────────────────────────────────────────────────────

    public function test_paid_plan_auto_renew_creates_invoice(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan(free: false);
        $sub  = $this->makeSubscription($user, $plan, true, Subscription::STATUS_ACTIVE, now()->addHours(12));

        $this->artisan('subscriptions:process-renewals')->assertSuccessful();

        // فاکتور pending ایجاد شده
        $this->assertDatabaseHas('invoices', [
            'user_id' => $user->id,
            'status'  => 'pending',
        ]);
    }

    public function test_paid_plan_not_activated_after_invoice(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan(free: false);
        $sub  = $this->makeSubscription($user, $plan, true, Subscription::STATUS_ACTIVE, now()->addHours(12));

        $originalEndsAt = $sub->ends_at->copy();

        $this->artisan('subscriptions:process-renewals')->assertSuccessful();

        // اشتراک هنوز فعال نشده (فقط فاکتور ایجاد شده)
        $sub->refresh();
        $this->assertEquals($originalEndsAt->toDateString(), $sub->ends_at->toDateString());
    }

    public function test_paid_plan_renewal_invoice_logged(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan(free: false);
        $sub  = $this->makeSubscription($user, $plan, true, Subscription::STATUS_ACTIVE, now()->addHours(12));

        $this->artisan('subscriptions:process-renewals')->assertSuccessful();

        $this->assertDatabaseHas('subscription_logs', [
            'subscription_id' => $sub->id,
            'action'          => SubscriptionLog::ACTION_AUTO_RENEW_INVOICE,
            'performed_by'    => 'system',
        ]);
    }

    public function test_paid_plan_duplicate_invoice_prevented(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan(free: false);
        $sub  = $this->makeSubscription($user, $plan, true, Subscription::STATUS_ACTIVE, now()->addHours(12));

        // اجرای دوباره
        $this->artisan('subscriptions:process-renewals')->assertSuccessful();
        $this->artisan('subscriptions:process-renewals')->assertSuccessful();

        // فقط یک فاکتور pending
        $invoiceCount = Invoice::where('user_id', $user->id)->where('status', 'pending')->count();
        $this->assertEquals(1, $invoiceCount);
    }

    // ─── dry-run test ─────────────────────────────────────────────────────────

    public function test_dry_run_makes_no_db_changes(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan(free: true);
        $sub  = $this->makeSubscription($user, $plan, true, Subscription::STATUS_ACTIVE, now()->addHours(12));

        $originalEndsAt = $sub->ends_at->copy();

        $this->artisan('subscriptions:process-renewals', ['--dry-run' => true])->assertSuccessful();

        $sub->refresh();
        $this->assertEquals($originalEndsAt->toDateString(), $sub->ends_at->toDateString());
        $this->assertEquals(0, SubscriptionLog::where('action', SubscriptionLog::ACTION_AUTO_RENEWED)->count());
    }

    public function test_command_exits_success_with_no_subscriptions(): void
    {
        $this->artisan('subscriptions:process-renewals')->assertSuccessful();
    }
}

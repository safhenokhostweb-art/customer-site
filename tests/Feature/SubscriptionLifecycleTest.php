<?php

namespace Tests\Feature;

use App\Helpers\Module;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\License;
use App\Models\Subscription;
use App\Models\SubscriptionLog;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

/**
 * P6 — Subscription Lifecycle: cancel, delete-pending, cancel-pending, logs, expire.
 *
 * Covers:
 *   - Cancel active subscription → STATUS_CANCELLED
 *   - Cancel trial subscription → STATUS_CANCELLED
 *   - Cannot cancel already-cancelled → error redirect
 *   - Cannot cancel another user's subscription → 404
 *   - Cancel stores reason in log
 *   - Cancel records ACTION_CANCELLED log entry
 *   - Delete pending subscription → record deleted, invoice cancelled
 *   - Cannot delete non-pending subscription → error
 *   - Cannot delete another user's pending → 404
 *   - cancelPending by invoice_id → invoice + subscription cancelled
 *   - Cannot cancelPending with another user's invoice → 404
 *   - Subscribe creates ACTION_SUBSCRIBED log
 *   - expireOverdue() expires overdue active subscriptions
 */
class SubscriptionLifecycleTest extends TestCase
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
            ['license_key' => 'P6-LIFECYCLE-LICENSE'],
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
            'mobile'   => '095' . str_pad(self::$seq, 8, '0', STR_PAD_LEFT),
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

    private function makeSubscription(User $user, SubscriptionPlan $plan, string $status = Subscription::STATUS_ACTIVE, array $extra = []): Subscription
    {
        return Subscription::create(array_merge([
            'user_id'              => $user->id,
            'subscription_plan_id' => $plan->id,
            'status'               => $status,
            'starts_at'            => now(),
            'ends_at'              => now()->addMonth(),
        ], $extra));
    }

    private function makePendingWithInvoice(User $user, SubscriptionPlan $plan): array
    {
        $invoice = Invoice::create([
            'invoice_number'  => 'INV-P6-' . uniqid(),
            'user_id'         => $user->id,
            'customer_name'   => $user->name,
            'customer_mobile' => $user->mobile,
            'subtotal'        => $plan->price,
            'total'           => $plan->price,
            'status'          => 'pending',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'title'      => 'اشتراک ' . $plan->name,
            'price'      => $plan->price,
            'quantity'   => 1,
            'total'      => $plan->price,
        ]);

        $sub = $this->makeSubscription($user, $plan, Subscription::STATUS_PENDING, [
            'invoice_id' => $invoice->id,
        ]);

        return [$sub, $invoice];
    }

    // ── cancel ────────────────────────────────────────────────────────────────

    public function test_user_can_cancel_active_subscription(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan();
        $sub  = $this->makeSubscription($user, $plan);

        $response = $this->actingAs($user)
            ->post(route('account.subscriptions.cancel', $sub));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('subscriptions', [
            'id'     => $sub->id,
            'status' => Subscription::STATUS_CANCELLED,
        ]);
    }

    public function test_user_can_cancel_trial_subscription(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan();
        $sub  = $this->makeSubscription($user, $plan, Subscription::STATUS_TRIAL, [
            'trial_ends_at' => now()->addDays(5),
        ]);

        $this->actingAs($user)->post(route('account.subscriptions.cancel', $sub));

        $this->assertDatabaseHas('subscriptions', [
            'id'     => $sub->id,
            'status' => Subscription::STATUS_CANCELLED,
        ]);
    }

    public function test_cannot_cancel_already_cancelled_subscription(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan();
        $sub  = $this->makeSubscription($user, $plan, Subscription::STATUS_CANCELLED, [
            'cancelled_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->post(route('account.subscriptions.cancel', $sub));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // وضعیت تغییر نکند
        $this->assertDatabaseHas('subscriptions', [
            'id'     => $sub->id,
            'status' => Subscription::STATUS_CANCELLED,
        ]);
    }

    public function test_cannot_cancel_other_users_subscription(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $plan  = $this->makePlan();
        $sub   = $this->makeSubscription($owner, $plan);

        $response = $this->actingAs($other)
            ->post(route('account.subscriptions.cancel', $sub));

        $response->assertStatus(404);
        $this->assertDatabaseHas('subscriptions', [
            'id'     => $sub->id,
            'status' => Subscription::STATUS_ACTIVE,
        ]);
    }

    public function test_cancel_stores_reason(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan();
        $sub  = $this->makeSubscription($user, $plan);

        $this->actingAs($user)->post(route('account.subscriptions.cancel', $sub), [
            'reason' => 'دیگر نیازی ندارم',
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'id'                  => $sub->id,
            'cancellation_reason' => 'دیگر نیازی ندارم',
        ]);
    }

    public function test_cancel_records_action_in_log(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan();
        $sub  = $this->makeSubscription($user, $plan);

        $this->actingAs($user)->post(route('account.subscriptions.cancel', $sub));

        $this->assertDatabaseHas('subscription_logs', [
            'subscription_id' => $sub->id,
            'action'          => SubscriptionLog::ACTION_CANCELLED,
        ]);
    }

    // ── delete pending ────────────────────────────────────────────────────────

    public function test_user_can_delete_pending_subscription(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan(['price' => 100000]);
        [$sub, $invoice] = $this->makePendingWithInvoice($user, $plan);

        $response = $this->actingAs($user)
            ->delete(route('account.subscriptions.delete-pending', $sub));

        $response->assertRedirect(route('account.subscriptions.index'));
        $response->assertSessionHas('success');

        // subscription حذف شده
        $this->assertDatabaseMissing('subscriptions', ['id' => $sub->id]);
        // invoice لغو شده
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'cancelled']);
    }

    public function test_cannot_delete_non_pending_subscription(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan();
        $sub  = $this->makeSubscription($user, $plan, Subscription::STATUS_ACTIVE);

        $response = $this->actingAs($user)
            ->delete(route('account.subscriptions.delete-pending', $sub));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('subscriptions', ['id' => $sub->id]);
    }

    public function test_cannot_delete_other_users_pending_subscription(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $plan  = $this->makePlan(['price' => 50000]);
        [$sub] = $this->makePendingWithInvoice($owner, $plan);

        $response = $this->actingAs($other)
            ->delete(route('account.subscriptions.delete-pending', $sub));

        $response->assertStatus(404);
        $this->assertDatabaseHas('subscriptions', ['id' => $sub->id]);
    }

    // ── cancelPending ─────────────────────────────────────────────────────────

    public function test_cancel_pending_by_invoice_id_cancels_invoice_and_subscription(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan(['price' => 80000]);
        [$sub, $invoice] = $this->makePendingWithInvoice($user, $plan);

        $response = $this->actingAs($user)
            ->post(route('account.subscriptions.cancel-pending'), ['invoice_id' => $invoice->id]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'cancelled']);
        $this->assertDatabaseMissing('subscriptions', ['id' => $sub->id]);
    }

    public function test_cannot_cancel_pending_with_another_users_invoice(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $plan  = $this->makePlan(['price' => 80000]);
        [, $invoice] = $this->makePendingWithInvoice($owner, $plan);

        $response = $this->actingAs($other)
            ->post(route('account.subscriptions.cancel-pending'), ['invoice_id' => $invoice->id]);

        // firstOrFail روی user_id filter → 404
        $response->assertStatus(404);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'pending']);
    }

    // ── logs ──────────────────────────────────────────────────────────────────

    public function test_subscribe_free_plan_creates_subscribed_log(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan(['price' => 0]);

        $this->actingAs($user)
            ->post(route('account.subscriptions.subscribe'), ['plan_id' => $plan->id]);

        $sub = Subscription::where('user_id', $user->id)->first();
        $this->assertNotNull($sub);

        $this->assertDatabaseHas('subscription_logs', [
            'subscription_id' => $sub->id,
            'action'          => SubscriptionLog::ACTION_SUBSCRIBED,
        ]);
    }

    // ── expire ────────────────────────────────────────────────────────────────

    public function test_expire_overdue_sets_expired_status_on_overdue_subscriptions(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan();

        // اشتراک منقضی‌شده (ends_at در گذشته)
        $overdue = $this->makeSubscription($user, $plan, Subscription::STATUS_ACTIVE, [
            'starts_at' => now()->subDays(35),
            'ends_at'   => now()->subDays(5),
        ]);

        // اشتراک هنوز فعال
        $active = $this->makeSubscription($this->makeUser(), $plan, Subscription::STATUS_ACTIVE, [
            'starts_at' => now(),
            'ends_at'   => now()->addDays(25),
        ]);

        $count = app(SubscriptionService::class)->expireOverdue();

        $this->assertEquals(1, $count);
        $this->assertDatabaseHas('subscriptions', [
            'id'     => $overdue->id,
            'status' => Subscription::STATUS_EXPIRED,
        ]);
        $this->assertDatabaseHas('subscriptions', [
            'id'     => $active->id,
            'status' => Subscription::STATUS_ACTIVE,
        ]);
    }
}

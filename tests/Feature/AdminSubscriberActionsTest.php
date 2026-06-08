<?php

namespace Tests\Feature;

use App\Helpers\Module;
use App\Models\License;
use App\Models\Subscription;
use App\Models\SubscriptionLog;
use App\Models\SubscriptionMember;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

/**
 * Admin Subscription Panel — force-extend, plan-change, group-member-remove.
 */
class AdminSubscriberActionsTest extends TestCase
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

        License::firstOrCreate(
            ['license_key' => 'ADMIN-SUB-ACT-LICENSE'],
            [
                'domain'            => '',
                'expires_at'        => now()->addYear(),
                'modules'           => ['subscription'],
                'platform_valid'    => true,
                'last_validated_at' => now(),
            ]
        );
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    // ── helpers ───────────────────────────────────────────────────────────────

    private function makeAdmin(): User
    {
        self::$seq++;
        return User::factory()->create([
            'mobile'   => '091' . str_pad(self::$seq, 8, '0', STR_PAD_LEFT),
            'is_admin' => true,
        ]);
    }

    private function makeUser(): User
    {
        self::$seq++;
        return User::factory()->create([
            'mobile'   => '092' . str_pad(self::$seq, 8, '0', STR_PAD_LEFT),
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
            'trial_days'     => 0,
            'order'          => 1,
        ], $attrs));
    }

    private function makeActiveSub(User $user, SubscriptionPlan $plan): Subscription
    {
        return Subscription::create([
            'user_id'              => $user->id,
            'subscription_plan_id' => $plan->id,
            'status'               => Subscription::STATUS_ACTIVE,
            'starts_at'            => now(),
            'ends_at'              => now()->addDays(30),
        ]);
    }

    // ── extend ────────────────────────────────────────────────────────────────

    /** @test */
    public function admin_can_extend_active_subscription_by_days(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();
        $plan  = $this->makePlan();
        $sub   = $this->makeActiveSub($user, $plan);

        $originalEndsAt = $sub->ends_at->copy();

        $this->actingAs($admin)
            ->post(route('admin.subscribers.extend', $sub), ['days' => 14])
            ->assertRedirect();

        $sub->refresh();
        $this->assertEquals(
            $originalEndsAt->addDays(14)->toDateString(),
            $sub->ends_at->toDateString()
        );
        $this->assertEquals(Subscription::STATUS_ACTIVE, $sub->status);
    }

    /** @test */
    public function extend_creates_renewal_log_entry(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();
        $plan  = $this->makePlan();
        $sub   = $this->makeActiveSub($user, $plan);

        $this->actingAs($admin)
            ->post(route('admin.subscribers.extend', $sub), ['days' => 7]);

        $log = SubscriptionLog::where('subscription_id', $sub->id)
            ->where('action', SubscriptionLog::ACTION_RENEWED)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('admin', $log->performed_by);
        $this->assertEquals(7, $log->details['extended_days']);
    }

    /** @test */
    public function extend_reactivates_expired_subscription(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();
        $plan  = $this->makePlan();
        $sub   = Subscription::create([
            'user_id'              => $user->id,
            'subscription_plan_id' => $plan->id,
            'status'               => Subscription::STATUS_EXPIRED,
            'starts_at'            => now()->subDays(40),
            'ends_at'              => now()->subDays(10),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.subscribers.extend', $sub), ['days' => 30]);

        $sub->refresh();
        $this->assertEquals(Subscription::STATUS_ACTIVE, $sub->status);
        $this->assertTrue($sub->ends_at->isFuture());
    }

    /** @test */
    public function extend_validates_days_range(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();
        $plan  = $this->makePlan();
        $sub   = $this->makeActiveSub($user, $plan);

        $this->actingAs($admin)
            ->post(route('admin.subscribers.extend', $sub), ['days' => 0])
            ->assertSessionHasErrors('days');

        $this->actingAs($admin)
            ->post(route('admin.subscribers.extend', $sub), ['days' => 366])
            ->assertSessionHasErrors('days');
    }

    /** @test */
    public function guest_cannot_extend_subscription(): void
    {
        $user = $this->makeUser();
        $plan = $this->makePlan();
        $sub  = $this->makeActiveSub($user, $plan);

        $endsAtBefore = $sub->ends_at->copy();

        $this->post(route('admin.subscribers.extend', $sub), ['days' => 7])
            ->assertRedirect();

        $sub->refresh();
        // تاریخ پایان نباید تغییر کرده باشد — guest redirect می‌شود بدون تغییر
        $this->assertEquals($endsAtBefore->toDateString(), $sub->ends_at->toDateString());
    }

    // ── changePlan ────────────────────────────────────────────────────────────

    /** @test */
    public function admin_can_change_subscription_plan(): void
    {
        $admin   = $this->makeAdmin();
        $user    = $this->makeUser();
        $oldPlan = $this->makePlan(['name' => 'پلن قدیمی', 'duration_count' => 1]);
        $newPlan = $this->makePlan(['name' => 'پلن جدید', 'duration_count' => 3]);
        $sub     = $this->makeActiveSub($user, $oldPlan);

        $this->actingAs($admin)
            ->post(route('admin.subscribers.change-plan', $sub), ['plan_id' => $newPlan->id])
            ->assertRedirect();

        $sub->refresh();
        $this->assertEquals($newPlan->id, $sub->subscription_plan_id);
        $this->assertEquals(Subscription::STATUS_ACTIVE, $sub->status);
    }

    /** @test */
    public function change_plan_creates_plan_changed_log(): void
    {
        $admin   = $this->makeAdmin();
        $user    = $this->makeUser();
        $oldPlan = $this->makePlan(['name' => 'پلن الف']);
        $newPlan = $this->makePlan(['name' => 'پلن ب']);
        $sub     = $this->makeActiveSub($user, $oldPlan);

        $this->actingAs($admin)
            ->post(route('admin.subscribers.change-plan', $sub), ['plan_id' => $newPlan->id]);

        $log = SubscriptionLog::where('subscription_id', $sub->id)
            ->where('action', SubscriptionLog::ACTION_PLAN_CHANGED)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('پلن الف', $log->details['from_plan']);
        $this->assertEquals('پلن ب', $log->details['to_plan']);
        $this->assertEquals('admin', $log->performed_by);
    }

    /** @test */
    public function change_plan_rejects_same_plan(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();
        $plan  = $this->makePlan();
        $sub   = $this->makeActiveSub($user, $plan);

        $this->actingAs($admin)
            ->post(route('admin.subscribers.change-plan', $sub), ['plan_id' => $plan->id])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    /** @test */
    public function change_plan_requires_valid_plan_id(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();
        $plan  = $this->makePlan();
        $sub   = $this->makeActiveSub($user, $plan);

        $this->actingAs($admin)
            ->post(route('admin.subscribers.change-plan', $sub), ['plan_id' => 99999])
            ->assertSessionHasErrors('plan_id');
    }

    // ── removeMember ──────────────────────────────────────────────────────────

    /** @test */
    public function admin_can_remove_active_group_member(): void
    {
        $admin  = $this->makeAdmin();
        $owner  = $this->makeUser();
        $member = $this->makeUser();
        $plan   = $this->makePlan(['max_users' => 5]);
        $sub    = $this->makeActiveSub($owner, $plan);

        $sm = SubscriptionMember::create([
            'subscription_id' => $sub->id,
            'user_id'         => $member->id,
            'invited_by'      => $owner->id,
            'status'          => SubscriptionMember::STATUS_ACTIVE,
            'invited_at'      => now(),
            'joined_at'       => now(),
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.subscribers.members.remove', [$sub, $sm]))
            ->assertRedirect();

        $sm->refresh();
        $this->assertEquals(SubscriptionMember::STATUS_REMOVED, $sm->status);
        $this->assertNotNull($sm->removed_at);
    }

    /** @test */
    public function admin_cannot_remove_member_from_wrong_subscription(): void
    {
        $admin   = $this->makeAdmin();
        $owner1  = $this->makeUser();
        $owner2  = $this->makeUser();
        $member  = $this->makeUser();
        $plan    = $this->makePlan();
        $sub1    = $this->makeActiveSub($owner1, $plan);
        $sub2    = $this->makeActiveSub($owner2, $plan);

        $sm = SubscriptionMember::create([
            'subscription_id' => $sub2->id,
            'user_id'         => $member->id,
            'invited_by'      => $owner2->id,
            'status'          => SubscriptionMember::STATUS_ACTIVE,
            'invited_at'      => now(),
            'joined_at'       => now(),
        ]);

        // sub1 در route، sm متعلق به sub2 است
        $this->actingAs($admin)
            ->delete(route('admin.subscribers.members.remove', [$sub1, $sm]))
            ->assertStatus(403);

        $sm->refresh();
        $this->assertEquals(SubscriptionMember::STATUS_ACTIVE, $sm->status);
    }

    /** @test */
    public function guest_cannot_remove_group_member(): void
    {
        $owner  = $this->makeUser();
        $member = $this->makeUser();
        $plan   = $this->makePlan();
        $sub    = $this->makeActiveSub($owner, $plan);

        $sm = SubscriptionMember::create([
            'subscription_id' => $sub->id,
            'user_id'         => $member->id,
            'invited_by'      => $owner->id,
            'status'          => SubscriptionMember::STATUS_ACTIVE,
            'invited_at'      => now(),
            'joined_at'       => now(),
        ]);

        $this->delete(route('admin.subscribers.members.remove', [$sub, $sm]))
            ->assertRedirect();

        $sm->refresh();
        $this->assertEquals(SubscriptionMember::STATUS_ACTIVE, $sm->status);
    }
}

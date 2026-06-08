<?php

namespace Tests\Feature;

use App\Helpers\Module;
use App\Models\License;
use App\Models\Subscription;
use App\Models\SubscriptionMember;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\GroupSubscriptionService;
use App\Services\MembershipBenefitService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use ReflectionClass;
use Tests\TestCase;

/**
 * P9 — Group Subscription
 *
 * Covers:
 *   1. availableSeats: plan with max_users=1 → 0 seats
 *   2. availableSeats: max_users=3, 1 active member → 1 seat
 *   3. invite: success → SubscriptionMember pending created
 *   4. invite: owner cannot invite themselves → RuntimeException
 *   5. invite: unknown mobile → RuntimeException
 *   6. invite: no seats → RuntimeException
 *   7. accept: pending invite → active, membership cache invalidated
 *   8. reject: pending invite → rejected
 *   9. remove: owner removes member → removed
 *  10. getActivePlan: group member gets owner's plan benefits
 *  11. HTTP: non-group plan shows 404 on members page
 *  12. HTTP: non-owner cannot access members page
 */
class GroupSubscriptionTest extends TestCase
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
            ['license_key' => 'P9-GROUP-LICENSE'],
            [
                'domain'            => '',
                'expires_at'        => now()->addYear(),
                'modules'           => ['subscription', 'shop'],
                'platform_valid'    => true,
                'last_validated_at' => now(),
            ]
        );
    }

    private function makeUser(string $name = ''): User
    {
        self::$seq++;
        return User::factory()->create([
            'name'     => $name ?: 'کاربر ' . self::$seq,
            'mobile'   => '098' . str_pad(self::$seq, 8, '0', STR_PAD_LEFT),
            'is_admin' => false,
        ]);
    }

    private function makePlan(int $maxUsers = 1, float $price = 0): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name'           => 'پلن ' . uniqid(),
            'slug'           => 'plan-' . uniqid(),
            'price'          => $price,
            'duration'       => 'month',
            'duration_count' => 1,
            'is_active'      => true,
            'max_users'      => $maxUsers,
        ]);
    }

    private function activeSub(User $user, SubscriptionPlan $plan): Subscription
    {
        return Subscription::create([
            'user_id'              => $user->id,
            'subscription_plan_id' => $plan->id,
            'status'               => Subscription::STATUS_ACTIVE,
            'starts_at'            => now(),
            'ends_at'              => now()->addMonth(),
            'credit_amount'        => 0,
        ]);
    }

    private function groupSvc(): GroupSubscriptionService
    {
        return app(GroupSubscriptionService::class);
    }

    // ── Tests ─────────────────────────────────────────────────────────────────

    /** @test */
    public function available_seats_is_zero_for_non_group_plan()
    {
        $owner = $this->makeUser();
        $plan  = $this->makePlan(maxUsers: 1);
        $sub   = $this->activeSub($owner, $plan);

        $this->assertEquals(0, $this->groupSvc()->availableSeats($sub));
    }

    /** @test */
    public function available_seats_counts_correctly()
    {
        $owner  = $this->makeUser();
        $member = $this->makeUser();
        $plan   = $this->makePlan(maxUsers: 3);
        $sub    = $this->activeSub($owner, $plan);

        SubscriptionMember::create([
            'subscription_id' => $sub->id,
            'user_id'         => $member->id,
            'invited_by'      => $owner->id,
            'status'          => SubscriptionMember::STATUS_ACTIVE,
            'invited_at'      => now(),
            'joined_at'       => now(),
        ]);

        // max_users=3, owner=1, active_member=1, pending=0 → 3-1-1=1
        $this->assertEquals(1, $this->groupSvc()->availableSeats($sub));
    }

    /** @test */
    public function invite_creates_pending_member()
    {
        $owner   = $this->makeUser();
        $invitee = $this->makeUser();
        $plan    = $this->makePlan(maxUsers: 3);
        $sub     = $this->activeSub($owner, $plan);

        $m = $this->groupSvc()->invite($sub, $owner, $invitee->mobile);

        $this->assertEquals(SubscriptionMember::STATUS_PENDING, $m->status);
        $this->assertEquals($invitee->id, $m->user_id);
        $this->assertEquals($owner->id, $m->invited_by);
        $this->assertDatabaseHas('subscription_members', [
            'subscription_id' => $sub->id,
            'user_id'         => $invitee->id,
            'status'          => SubscriptionMember::STATUS_PENDING,
        ]);
    }

    /** @test */
    public function invite_throws_when_owner_invites_themselves()
    {
        $owner = $this->makeUser();
        $plan  = $this->makePlan(maxUsers: 3);
        $sub   = $this->activeSub($owner, $plan);

        $this->expectException(\RuntimeException::class);
        $this->groupSvc()->invite($sub, $owner, $owner->mobile);
    }

    /** @test */
    public function invite_throws_for_unknown_mobile()
    {
        $owner = $this->makeUser();
        $plan  = $this->makePlan(maxUsers: 3);
        $sub   = $this->activeSub($owner, $plan);

        $this->expectException(\RuntimeException::class);
        $this->groupSvc()->invite($sub, $owner, '09000000000');
    }

    /** @test */
    public function invite_throws_when_no_seats_available()
    {
        $owner  = $this->makeUser();
        $member = $this->makeUser();
        $extra  = $this->makeUser();
        $plan   = $this->makePlan(maxUsers: 2); // 1 seat for members
        $sub    = $this->activeSub($owner, $plan);

        // Fill the only seat
        SubscriptionMember::create([
            'subscription_id' => $sub->id,
            'user_id'         => $member->id,
            'invited_by'      => $owner->id,
            'status'          => SubscriptionMember::STATUS_ACTIVE,
            'invited_at'      => now(),
            'joined_at'       => now(),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->groupSvc()->invite($sub, $owner, $extra->mobile);
    }

    /** @test */
    public function accept_activates_pending_member()
    {
        $owner   = $this->makeUser();
        $invitee = $this->makeUser();
        $plan    = $this->makePlan(maxUsers: 3);
        $sub     = $this->activeSub($owner, $plan);

        $m = SubscriptionMember::create([
            'subscription_id' => $sub->id,
            'user_id'         => $invitee->id,
            'invited_by'      => $owner->id,
            'status'          => SubscriptionMember::STATUS_PENDING,
            'invited_at'      => now(),
        ]);

        $this->groupSvc()->accept($m, $invitee);

        $this->assertDatabaseHas('subscription_members', [
            'id'     => $m->id,
            'status' => SubscriptionMember::STATUS_ACTIVE,
        ]);
        $this->assertNotNull($m->fresh()->joined_at);
    }

    /** @test */
    public function reject_marks_invite_as_rejected()
    {
        $owner   = $this->makeUser();
        $invitee = $this->makeUser();
        $plan    = $this->makePlan(maxUsers: 3);
        $sub     = $this->activeSub($owner, $plan);

        $m = SubscriptionMember::create([
            'subscription_id' => $sub->id,
            'user_id'         => $invitee->id,
            'invited_by'      => $owner->id,
            'status'          => SubscriptionMember::STATUS_PENDING,
            'invited_at'      => now(),
        ]);

        $this->groupSvc()->reject($m, $invitee);

        $this->assertDatabaseHas('subscription_members', [
            'id'     => $m->id,
            'status' => SubscriptionMember::STATUS_REJECTED,
        ]);
    }

    /** @test */
    public function remove_sets_member_status_to_removed()
    {
        $owner  = $this->makeUser();
        $member = $this->makeUser();
        $plan   = $this->makePlan(maxUsers: 3);
        $sub    = $this->activeSub($owner, $plan);

        $m = SubscriptionMember::create([
            'subscription_id' => $sub->id,
            'user_id'         => $member->id,
            'invited_by'      => $owner->id,
            'status'          => SubscriptionMember::STATUS_ACTIVE,
            'invited_at'      => now(),
            'joined_at'       => now(),
        ]);

        $result = $this->groupSvc()->remove($m, $owner);

        $this->assertTrue($result);
        $this->assertDatabaseHas('subscription_members', [
            'id'     => $m->id,
            'status' => SubscriptionMember::STATUS_REMOVED,
        ]);
    }

    /** @test */
    public function group_member_inherits_owner_plan_benefits()
    {
        $owner  = $this->makeUser();
        $member = $this->makeUser();
        $plan   = $this->makePlan(maxUsers: 3, price: 120000);
        $sub    = $this->activeSub($owner, $plan);

        // Add active membership
        SubscriptionMember::create([
            'subscription_id' => $sub->id,
            'user_id'         => $member->id,
            'invited_by'      => $owner->id,
            'status'          => SubscriptionMember::STATUS_ACTIVE,
            'invited_at'      => now(),
            'joined_at'       => now(),
        ]);

        // Member has no own subscription — should get plan via group
        Cache::flush();
        $activePlan = app(SubscriptionService::class)->getActivePlan($member);

        $this->assertNotNull($activePlan);
        $this->assertEquals($plan->id, $activePlan->id);
    }

    /** @test */
    public function group_member_benefit_cache_is_invalidated_when_subscription_expires()
    {
        $owner  = $this->makeUser();
        $member = $this->makeUser();
        $plan   = $this->makePlan(maxUsers: 3, price: 120000);
        $sub    = $this->activeSub($owner, $plan);

        SubscriptionMember::create([
            'subscription_id' => $sub->id,
            'user_id'         => $member->id,
            'invited_by'      => $owner->id,
            'status'          => SubscriptionMember::STATUS_ACTIVE,
            'invited_at'      => now(),
            'joined_at'       => now(),
        ]);

        // Warm cache for member
        $benefitSvc = app(MembershipBenefitService::class);
        $benefitSvc->getActivePlan($member); // caches the plan
        $this->assertNotNull(Cache::get("membership_plan_{$member->id}"));

        // Expire the owner's subscription
        app(SubscriptionService::class)->expire($sub);

        // Cache must be cleared
        $this->assertNull(Cache::get("membership_plan_{$member->id}"));
    }

    /** @test */
    public function group_member_benefit_cache_is_invalidated_when_subscription_is_cancelled()
    {
        $owner  = $this->makeUser();
        $member = $this->makeUser();
        $plan   = $this->makePlan(maxUsers: 3, price: 120000);
        $sub    = $this->activeSub($owner, $plan);

        SubscriptionMember::create([
            'subscription_id' => $sub->id,
            'user_id'         => $member->id,
            'invited_by'      => $owner->id,
            'status'          => SubscriptionMember::STATUS_ACTIVE,
            'invited_at'      => now(),
            'joined_at'       => now(),
        ]);

        // Warm cache
        $benefitSvc = app(MembershipBenefitService::class);
        $benefitSvc->getActivePlan($member);
        $this->assertNotNull(Cache::get("membership_plan_{$member->id}"));

        // Cancel the owner's subscription
        app(SubscriptionService::class)->cancel($sub, 'test cancel', 'user');

        // Cache must be cleared
        $this->assertNull(Cache::get("membership_plan_{$member->id}"));
    }

    /** @test */
    public function members_page_is_inaccessible_for_non_group_plan()
    {
        $owner = $this->makeUser();
        $plan  = $this->makePlan(maxUsers: 1);
        $sub   = $this->activeSub($owner, $plan);

        $this->actingAs($owner)
            ->get(route('account.subscriptions.members', $sub))
            ->assertRedirect(route('account.subscriptions.show', $sub));
    }

    /** @test */
    public function members_page_returns_404_for_non_owner()
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $plan  = $this->makePlan(maxUsers: 3);
        $sub   = $this->activeSub($owner, $plan);

        $this->actingAs($other)
            ->get(route('account.subscriptions.members', $sub))
            ->assertNotFound();
    }
}

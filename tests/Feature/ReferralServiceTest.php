<?php

namespace Tests\Feature;

use App\Models\MembershipPoint;
use App\Models\Referral;
use App\Models\ReferralCode;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * ReferralService — Faz 4 Growth Engine tests.
 *
 * Covers:
 *   - getOrCreateCode: creates code for new user
 *   - getOrCreateCode: returns same code on second call (idempotent)
 *   - registerReferral: returns null for unknown code
 *   - registerReferral: returns null for inactive code
 *   - registerReferral: creates referral record
 *   - registerReferral: increments uses count
 *   - registerReferral: self-referral blocked
 *   - registerReferral: duplicate registration blocked
 *   - rewardReferral: skips if no pending referral
 *   - rewardReferral: grants points to both parties
 *   - rewardReferral: marks referral as rewarded
 *   - rewardReferral: idempotent (does not double-reward)
 *   - getStats: correct counts
 *   - register endpoint stores ref_code and creates referral
 */
class ReferralServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReferralService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        config(['site.owner_site' => true]);
        Cache::flush();
        $this->svc = app(ReferralService::class);
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    // ─── getOrCreateCode ──────────────────────────────────────────────────

    public function test_get_or_create_code_creates_for_new_user(): void
    {
        $user = User::factory()->create();
        $code = $this->svc->getOrCreateCode($user);

        $this->assertNotNull($code);
        $this->assertEquals($user->id, $code->user_id);
        $this->assertNotEmpty($code->code);
        $this->assertTrue($code->is_active);
    }

    public function test_get_or_create_code_is_idempotent(): void
    {
        $user  = User::factory()->create();
        $code1 = $this->svc->getOrCreateCode($user);
        $code2 = $this->svc->getOrCreateCode($user);

        $this->assertEquals($code1->id, $code2->id);
        $this->assertEquals(1, ReferralCode::where('user_id', $user->id)->count());
    }

    // ─── registerReferral ─────────────────────────────────────────────────

    public function test_register_referral_returns_null_for_unknown_code(): void
    {
        $newUser = User::factory()->create();
        $result  = $this->svc->registerReferral($newUser, 'BOGUS999');

        $this->assertNull($result);
    }

    public function test_register_referral_returns_null_for_inactive_code(): void
    {
        $referrer = User::factory()->create();
        ReferralCode::create(['user_id' => $referrer->id, 'code' => 'INACTIVE', 'is_active' => false]);

        $newUser = User::factory()->create();
        $result  = $this->svc->registerReferral($newUser, 'INACTIVE');

        $this->assertNull($result);
    }

    public function test_register_referral_creates_referral_record(): void
    {
        $referrer = User::factory()->create();
        $code     = $this->svc->getOrCreateCode($referrer);
        $newUser  = User::factory()->create();

        $referral = $this->svc->registerReferral($newUser, $code->code);

        $this->assertNotNull($referral);
        $this->assertEquals(Referral::STATUS_PENDING, $referral->status);
        $this->assertEquals($newUser->id, $referral->referred_user_id);
        $this->assertEquals($code->id, $referral->referral_code_id);
    }

    public function test_register_referral_increments_uses(): void
    {
        $referrer = User::factory()->create();
        $code     = $this->svc->getOrCreateCode($referrer);
        $newUser  = User::factory()->create();

        $this->svc->registerReferral($newUser, $code->code);

        $this->assertEquals(1, $code->fresh()->uses);
    }

    public function test_self_referral_is_blocked(): void
    {
        $user   = User::factory()->create();
        $code   = $this->svc->getOrCreateCode($user);
        $result = $this->svc->registerReferral($user, $code->code);

        $this->assertNull($result);
        $this->assertEquals(0, Referral::count());
    }

    public function test_duplicate_registration_is_blocked(): void
    {
        $referrer1 = User::factory()->create();
        $referrer2 = User::factory()->create();
        $code1     = $this->svc->getOrCreateCode($referrer1);
        $code2     = $this->svc->getOrCreateCode($referrer2);
        $newUser   = User::factory()->create();

        $this->svc->registerReferral($newUser, $code1->code);
        $result = $this->svc->registerReferral($newUser, $code2->code);

        $this->assertNull($result);
        $this->assertEquals(1, Referral::where('referred_user_id', $newUser->id)->count());
    }

    // ─── rewardReferral ───────────────────────────────────────────────────

    public function test_reward_referral_skips_if_no_pending_referral(): void
    {
        $user = User::factory()->create();
        $this->svc->rewardReferral($user);

        $this->assertEquals(0, MembershipPoint::count());
    }

    public function test_reward_referral_grants_points_to_both_parties(): void
    {
        $referrer = User::factory()->create();
        $code     = $this->svc->getOrCreateCode($referrer);
        $referred = User::factory()->create();
        $this->svc->registerReferral($referred, $code->code);

        $this->svc->rewardReferral($referred);

        $referrerPoints = MembershipPoint::where('user_id', $referrer->id)->sum('amount');
        $referredPoints = MembershipPoint::where('user_id', $referred->id)->sum('amount');

        $this->assertEquals(ReferralService::POINTS_REFERRER, $referrerPoints);
        $this->assertEquals(ReferralService::POINTS_REFERRED, $referredPoints);
    }

    public function test_reward_referral_marks_as_rewarded(): void
    {
        $referrer = User::factory()->create();
        $code     = $this->svc->getOrCreateCode($referrer);
        $referred = User::factory()->create();
        $this->svc->registerReferral($referred, $code->code);

        $this->svc->rewardReferral($referred);

        $referral = Referral::where('referred_user_id', $referred->id)->first();
        $this->assertEquals(Referral::STATUS_REWARDED, $referral->status);
        $this->assertNotNull($referral->rewarded_at);
        $this->assertEquals(
            ReferralService::POINTS_REFERRER + ReferralService::POINTS_REFERRED,
            $referral->points_awarded
        );
    }

    public function test_reward_referral_is_idempotent(): void
    {
        $referrer = User::factory()->create();
        $code     = $this->svc->getOrCreateCode($referrer);
        $referred = User::factory()->create();
        $this->svc->registerReferral($referred, $code->code);

        $this->svc->rewardReferral($referred);
        $this->svc->rewardReferral($referred); // دوباره

        // فقط یک بار امتیاز داده می‌شود
        $referrerPoints = MembershipPoint::where('user_id', $referrer->id)->count();
        $this->assertEquals(1, $referrerPoints);
    }

    // ─── getStats ────────────────────────────────────────────────────────

    public function test_get_stats_returns_correct_counts(): void
    {
        $referrer  = User::factory()->create();
        $code      = $this->svc->getOrCreateCode($referrer);
        $referred1 = User::factory()->create();
        $referred2 = User::factory()->create();

        $this->svc->registerReferral($referred1, $code->code);
        $this->svc->registerReferral($referred2, $code->code);
        $this->svc->rewardReferral($referred1);

        $stats = $this->svc->getStats($referrer);

        $this->assertEquals(2, $stats['total']);
        $this->assertEquals(1, $stats['rewarded']);
        $this->assertEquals(1, $stats['pending']);
        $this->assertNotEmpty($stats['link']);
    }

    // ─── Register endpoint integration ──────────────────────────────────

    public function test_register_with_ref_code_creates_referral(): void
    {
        config(['app.modules.referral' => true]);

        $referrer = User::factory()->create();
        $code     = $this->svc->getOrCreateCode($referrer);

        $response = $this->post(route('register'), [
            'name'                  => 'کاربر جدید',
            'mobile'                => '09123456789',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
            'ref_code'              => $code->code,
        ]);

        $response->assertRedirect();
        $newUser = User::where('mobile', '09123456789')->first();
        $this->assertNotNull($newUser);
        $this->assertEquals(1, Referral::where('referred_user_id', $newUser->id)->count());
    }

    public function test_register_without_ref_code_no_referral(): void
    {
        $this->post(route('register'), [
            'name'                  => 'کاربر دوم',
            'mobile'                => '09111111111',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $this->assertEquals(0, Referral::count());
    }
}

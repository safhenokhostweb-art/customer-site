<?php

namespace Tests\Feature;

use App\Helpers\Module;
use App\Models\License;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

/**
 * Auto-renew toggle + Post-expiry CTA tests.
 */
class SubscriptionUXTest extends TestCase
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

        License::query()->delete();
        License::create([
            'license_key'       => 'SUB-UX-TEST-LICENSE',
            'domain'            => '',
            'expires_at'        => now()->addYear(),
            'modules'           => ['subscription'],
            'platform_valid'    => true,
            'last_validated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function makeUser(): User
    {
        self::$seq++;
        return User::factory()->create([
            'mobile' => '091' . str_pad((string) self::$seq, 8, '0', STR_PAD_LEFT),
        ]);
    }

    private function makePlan(string $suffix = ''): SubscriptionPlan
    {
        self::$seq++;
        return SubscriptionPlan::create([
            'name'           => 'پلن ' . self::$seq . $suffix,
            'slug'           => 'plan-' . self::$seq . $suffix,
            'price'          => 0,
            'duration'       => 'month',
            'duration_count' => 1,
            'is_active'      => true,
        ]);
    }

    private function makeActiveSubscription(User $user, bool $autoRenew = false): Subscription
    {
        $plan = $this->makePlan();
        return Subscription::create([
            'user_id'              => $user->id,
            'subscription_plan_id' => $plan->id,
            'status'               => Subscription::STATUS_ACTIVE,
            'starts_at'            => now()->subDay(),
            'ends_at'              => now()->addMonth(),
            'auto_renew'           => $autoRenew,
        ]);
    }

    private function makeExpiredSubscription(User $user): Subscription
    {
        $plan = $this->makePlan();
        return Subscription::create([
            'user_id'              => $user->id,
            'subscription_plan_id' => $plan->id,
            'status'               => Subscription::STATUS_EXPIRED,
            'starts_at'            => now()->subMonths(2),
            'ends_at'              => now()->subMonth(),
        ]);
    }

    // ─── Auto-renew toggle ────────────────────────────────────────────────────

    public function test_user_can_enable_auto_renew(): void
    {
        $user = $this->makeUser();
        $sub  = $this->makeActiveSubscription($user, autoRenew: false);

        $response = $this->actingAs($user)
            ->post(route('account.subscriptions.toggle-auto-renew', $sub));

        $response->assertRedirect();
        $this->assertTrue($sub->fresh()->auto_renew);
    }

    public function test_user_can_disable_auto_renew(): void
    {
        $user = $this->makeUser();
        $sub  = $this->makeActiveSubscription($user, autoRenew: true);

        $response = $this->actingAs($user)
            ->post(route('account.subscriptions.toggle-auto-renew', $sub));

        $response->assertRedirect();
        $this->assertFalse($sub->fresh()->auto_renew);
    }

    public function test_toggle_auto_renew_shows_success_message(): void
    {
        $user = $this->makeUser();
        $sub  = $this->makeActiveSubscription($user, autoRenew: false);

        $this->actingAs($user)
            ->post(route('account.subscriptions.toggle-auto-renew', $sub))
            ->assertSessionHas('success');
    }

    public function test_cannot_toggle_auto_renew_for_expired_subscription(): void
    {
        $user = $this->makeUser();
        $sub  = $this->makeExpiredSubscription($user);

        $response = $this->actingAs($user)
            ->post(route('account.subscriptions.toggle-auto-renew', $sub));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertFalse((bool) $sub->fresh()->auto_renew);
    }

    public function test_cannot_toggle_another_users_auto_renew(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $sub   = $this->makeActiveSubscription($owner);

        $this->actingAs($other)
            ->post(route('account.subscriptions.toggle-auto-renew', $sub))
            ->assertNotFound();
    }

    public function test_auto_renew_badge_shown_on_index_when_active(): void
    {
        $user = $this->makeUser();
        $this->makeActiveSubscription($user, autoRenew: true);

        $response = $this->actingAs($user)->get(route('account.subscriptions.index'));
        $response->assertOk();
        $response->assertSee('تمدید خودکار فعال');
    }

    public function test_auto_renew_badge_hidden_when_disabled(): void
    {
        $user = $this->makeUser();
        $this->makeActiveSubscription($user, autoRenew: false);

        $response = $this->actingAs($user)->get(route('account.subscriptions.index'));
        $response->assertOk();
        $response->assertDontSee('تمدید خودکار فعال');
    }

    public function test_show_page_displays_auto_renew_toggle_button(): void
    {
        $user = $this->makeUser();
        $sub  = $this->makeActiveSubscription($user);

        $response = $this->actingAs($user)->get(route('account.subscriptions.show', $sub));
        $response->assertOk();
        $response->assertSee(route('account.subscriptions.toggle-auto-renew', $sub));
    }

    // ─── Post-expiry CTA ──────────────────────────────────────────────────────

    public function test_expired_subscription_shows_renew_cta_on_show_page(): void
    {
        $user = $this->makeUser();
        $sub  = $this->makeExpiredSubscription($user);

        $response = $this->actingAs($user)->get(route('account.subscriptions.show', $sub));
        $response->assertOk();
        $response->assertSee('اشتراک شما منقضی شده است');
        $response->assertSee(route('account.subscriptions.plans'));
    }

    public function test_expired_cta_on_index_when_no_active_subscription(): void
    {
        $user = $this->makeUser();
        $this->makeExpiredSubscription($user);

        $response = $this->actingAs($user)->get(route('account.subscriptions.index'));
        $response->assertOk();
        $response->assertSee('منقضی شده است');
        $response->assertSee('تمدید اشتراک');
    }

    public function test_generic_cta_shown_when_no_subscriptions_at_all(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->get(route('account.subscriptions.index'));
        $response->assertOk();
        $response->assertSee('اشتراک فعالی ندارید');
        $response->assertDontSee('منقضی شده است');
    }

    public function test_renew_button_shown_for_expired_row_in_history_table(): void
    {
        $user = $this->makeUser();
        $this->makeExpiredSubscription($user);

        $response = $this->actingAs($user)->get(route('account.subscriptions.index'));
        $response->assertOk();
        $response->assertSee('تمدید');
    }

    public function test_active_subscription_does_not_show_expired_cta(): void
    {
        $user = $this->makeUser();
        $this->makeActiveSubscription($user);

        $response = $this->actingAs($user)->get(route('account.subscriptions.index'));
        $response->assertOk();
        $response->assertDontSee('منقضی شده است');
        $response->assertSee('فعال');
    }
}

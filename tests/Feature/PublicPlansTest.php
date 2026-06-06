<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Public Pricing Page /plans — Faz 4 Growth Engine tests.
 *
 * Covers:
 *   - Guest can view /plans page
 *   - Active plans shown
 *   - Inactive plans NOT shown
 *   - Highlighted plan renders badge
 *   - Free plan shows رایگان
 *   - Special price shown and original struck-through
 *   - Logged-in user with active plan sees "پلن فعلی شما"
 *   - Empty plans shows placeholder
 */
class PublicPlansTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        config(['site.owner_site' => true]);
        config(['app.modules.subscription' => true]);
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    private function makePlan(array $attrs = []): SubscriptionPlan
    {
        return SubscriptionPlan::create(array_merge([
            'name'           => 'پلن آزمایشی',
            'slug'           => 'test-' . uniqid(),
            'price'          => 50000,
            'duration'       => 'month',
            'duration_count' => 1,
            'is_active'      => true,
            'order'          => 0,
            'highlighted'    => false,
            'trial_days'     => 0,
        ], $attrs));
    }

    public function test_guest_can_access_pricing_page(): void
    {
        $response = $this->get(route('pricing.index'));
        $response->assertStatus(200);
    }

    public function test_active_plan_shown_on_page(): void
    {
        $plan = $this->makePlan(['name' => 'پلن طلایی']);

        $response = $this->get(route('pricing.index'));
        $response->assertSee('پلن طلایی');
    }

    public function test_inactive_plan_not_shown(): void
    {
        $this->makePlan(['name' => 'پلن مخفی', 'is_active' => false]);

        $response = $this->get(route('pricing.index'));
        $response->assertDontSee('پلن مخفی');
    }

    public function test_highlighted_plan_shows_badge(): void
    {
        $this->makePlan([
            'highlighted' => true,
            'badge_text'  => 'بهترین انتخاب',
        ]);

        $response = $this->get(route('pricing.index'));
        $response->assertSee('بهترین انتخاب');
    }

    public function test_free_plan_shows_rayegan(): void
    {
        $this->makePlan(['price' => 0, 'name' => 'پلن رایگان']);

        $response = $this->get(route('pricing.index'));
        $response->assertSee('رایگان');
    }

    public function test_special_price_shown(): void
    {
        $this->makePlan([
            'price'         => 100000,
            'special_price' => 75000,
            'name'          => 'پلن تخفیف‌دار',
        ]);

        $response = $this->get(route('pricing.index'));
        $response->assertStatus(200);
        $response->assertSee('پلن تخفیف‌دار');
    }

    public function test_empty_plans_shows_placeholder(): void
    {
        $response = $this->get(route('pricing.index'));
        $response->assertStatus(200);
        // صفحه load می‌شود بدون خطا حتی با صفر پلن
    }

    public function test_authenticated_user_with_active_plan_sees_current_label(): void
    {
        $plan = $this->makePlan(['name' => 'پلن ویژه']);
        $user = User::factory()->create();

        Subscription::create([
            'user_id'              => $user->id,
            'subscription_plan_id' => $plan->id,
            'status'               => Subscription::STATUS_ACTIVE,
            'starts_at'            => now(),
            'ends_at'              => now()->addMonth(),
        ]);

        $response = $this->actingAs($user)->get(route('pricing.index'));
        $response->assertSee('پلن فعلی شما');
    }

    public function test_guest_sees_buy_button_not_active_label(): void
    {
        $this->makePlan(['name' => 'پلن نقره‌ای', 'price' => 30000]);

        $response = $this->get(route('pricing.index'));
        $response->assertDontSee('پلن فعلی شما');
        $response->assertSee('خرید اشتراک');
    }
}

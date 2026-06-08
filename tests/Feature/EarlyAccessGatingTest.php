<?php

namespace Tests\Feature;

use App\Helpers\Module;
use App\Models\License;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use ReflectionClass;
use Tests\TestCase;

/**
 * P7 — Early Access Product Gating
 *
 * Covers:
 *   - Early access product visible in shop listing to everyone (badge shown)
 *   - Guest cannot view early access product detail → redirect to login
 *   - Non-member cannot view early access product detail → redirect to plans
 *   - Member with regular plan (no early_access) cannot view detail → redirect to plans
 *   - Member with early_access plan can view product detail
 *   - Non-member cannot add early access product to cart
 *   - Member with early_access plan can add to cart
 *   - Regular product (early_access=false) accessible to everyone
 */
class EarlyAccessGatingTest extends TestCase
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

    // ── helpers ───────────────────────────────────────────────────────────────

    private function seedLicense(): void
    {
        License::firstOrCreate(
            ['license_key' => 'P7-EA-LICENSE'],
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
            'mobile'   => '096' . str_pad(self::$seq, 8, '0', STR_PAD_LEFT),
            'is_admin' => false,
        ]);
    }

    private function makeProduct(bool $earlyAccess = false): Product
    {
        return Product::query()->create([
            'title'        => ($earlyAccess ? 'محصول EA ' : 'محصول ') . uniqid(),
            'slug'         => 'prod-' . uniqid(),
            'price'        => 100000,
            'status'       => Product::STATUS_PUBLISHED,
            'stock'        => 999,
            'early_access' => $earlyAccess,
            'allow_backorder' => true,
        ]);
    }

    private function makePlan(array $attrs = []): SubscriptionPlan
    {
        return SubscriptionPlan::create(array_merge([
            'name'           => 'پلن ' . uniqid(),
            'slug'           => 'plan-' . uniqid(),
            'price'          => 0,
            'duration'       => 'month',
            'duration_count' => 1,
            'is_active'      => true,
            'early_access'   => false,
        ], $attrs));
    }

    private function activatePlan(User $user, SubscriptionPlan $plan): Subscription
    {
        return Subscription::create([
            'user_id'              => $user->id,
            'subscription_plan_id' => $plan->id,
            'status'               => Subscription::STATUS_ACTIVE,
            'starts_at'            => now(),
            'ends_at'              => now()->addMonth(),
        ]);
    }

    // ── tests ─────────────────────────────────────────────────────────────────

    public function test_early_access_product_visible_in_listing_to_everyone(): void
    {
        $product = $this->makeProduct(earlyAccess: true);

        $response = $this->get(route('shop.index'));

        $response->assertStatus(200);
        $response->assertSee($product->title);
        $response->assertSee('دسترسی زودهنگام');
    }

    public function test_guest_cannot_view_early_access_product_detail(): void
    {
        $product = $this->makeProduct(earlyAccess: true);

        $response = $this->get(route('shop.show', $product->slug));

        $response->assertRedirect(route('login'));
    }

    public function test_non_member_cannot_view_early_access_product_detail(): void
    {
        $user    = $this->makeUser();
        $product = $this->makeProduct(earlyAccess: true);

        $response = $this->actingAs($user)->get(route('shop.show', $product->slug));

        $response->assertRedirect(route('account.subscriptions.plans'));
        $response->assertSessionHas('error');
    }

    public function test_member_with_regular_plan_cannot_view_early_access_product(): void
    {
        $user    = $this->makeUser();
        $plan    = $this->makePlan(['early_access' => false]);
        $this->activatePlan($user, $plan);
        $product = $this->makeProduct(earlyAccess: true);

        $response = $this->actingAs($user)->get(route('shop.show', $product->slug));

        $response->assertRedirect(route('account.subscriptions.plans'));
        $response->assertSessionHas('error');
    }

    public function test_member_with_early_access_plan_can_view_product_detail(): void
    {
        $user    = $this->makeUser();
        $plan    = $this->makePlan(['early_access' => true]);
        $this->activatePlan($user, $plan);
        $product = $this->makeProduct(earlyAccess: true);

        $response = $this->actingAs($user)->get(route('shop.show', $product->slug));

        $response->assertStatus(200);
        $response->assertSee($product->title);
        $response->assertSee('دسترسی زودهنگام');
    }

    public function test_non_member_cannot_add_early_access_product_to_cart(): void
    {
        $user    = $this->makeUser();
        $product = $this->makeProduct(earlyAccess: true);

        $response = $this->actingAs($user)->post(route('shop.cart.add'), [
            'product_id' => $product->id,
            'quantity'   => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEmpty(session('cart', []));
    }

    public function test_early_access_member_can_add_early_access_product_to_cart(): void
    {
        $user    = $this->makeUser();
        $plan    = $this->makePlan(['early_access' => true]);
        $this->activatePlan($user, $plan);
        $product = $this->makeProduct(earlyAccess: true);

        $response = $this->actingAs($user)->post(route('shop.cart.add'), [
            'product_id' => $product->id,
            'quantity'   => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_regular_product_accessible_to_everyone(): void
    {
        $product = $this->makeProduct(earlyAccess: false);

        // Guest
        $this->get(route('shop.show', $product->slug))->assertStatus(200);

        // Non-member
        $user = $this->makeUser();
        $this->actingAs($user)->get(route('shop.show', $product->slug))->assertStatus(200);
    }
}

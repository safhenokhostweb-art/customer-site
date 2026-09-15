<?php

namespace Tests\Feature\ThemeCanvas;

use App\Helpers\CurrencyHelper;
use App\Models\InstallmentPlan;
use App\Models\License;
use App\Models\Product;
use App\Services\ThemeCanvas\BlockRegistry;
use App\Services\ThemeCanvas\ThemeCanvasRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * MOD-02 (Full Product Commercial Audit, TASK 2A-CONTINUATION):
 * `module:installment_badge` reuses the real, already-working
 * InstallmentService::eligiblePlans()/InstallmentPlan::calculate() backend
 * — the exact same code the public installment flow
 * (InstallmentController::plans()/apply()) uses — no new rate/eligibility
 * calculation invented here.
 */
class InstallmentBadgeModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        License::query()->create([
            'license_key' => 'INST-' . uniqid(),
            'domain' => request()->getHost(),
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => ['shop'],
        ]);
    }

    private function renderer(): ThemeCanvasRenderer
    {
        $renderer = app(ThemeCanvasRenderer::class);
        $renderer->setThemeData(['pages' => []]);
        return $renderer;
    }

    private function product(string $slug, int $price): Product
    {
        return Product::create([
            'title' => 'محصول ' . $slug, 'slug' => $slug, 'price' => $price,
            'status' => Product::STATUS_PUBLISHED, 'order' => 0,
        ]);
    }

    private function plan(array $overrides = []): InstallmentPlan
    {
        return InstallmentPlan::create(array_merge([
            'name' => 'طرح ۶ ماهه',
            'period_months' => 6,
            'down_payment_percent' => 20,
            'monthly_interest_rate' => 0,
            'min_amount' => 0,
            'max_amount' => 0,
            'is_active' => true,
        ], $overrides));
    }

    // ── Schema: real repeater-free module block, correctly registered ─────

    public function test_module_is_registered_with_the_real_schema(): void
    {
        $def = BlockRegistry::all()['module:installment_badge'] ?? null;
        self::assertNotNull($def);
        self::assertSame('shop', $def['requires']);
        self::assertSame(['product_id', 'style'], array_keys($def['schema']));
    }

    // ── No context: no product selected ────────────────────────────────────

    public function test_no_product_selected_shows_a_real_empty_state_not_a_fabricated_badge(): void
    {
        $this->plan();
        $html = $this->renderer()->renderBlock(['id' => 'ib1', 'type' => 'module:installment_badge', 'props' => []]);
        $this->assertStringContainsString('محصولی انتخاب نشده است', $html);
        $this->assertStringNotContainsString('خرید اقساطی از', $html);
    }

    // ── Invalid product ──────────────────────────────────────────────────

    public function test_invalid_product_id_shows_a_real_empty_state(): void
    {
        $this->plan();
        $html = $this->renderer()->renderBlock(['id' => 'ib2', 'type' => 'module:installment_badge', 'props' => ['product_id' => 999999]]);
        $this->assertStringContainsString('محصول یافت نشد', $html);
    }

    // ── Ineligible: product price outside every plan's range ───────────────

    public function test_product_with_no_eligible_plan_shows_a_real_unavailable_state(): void
    {
        $this->plan(['min_amount' => 5_000_000, 'max_amount' => 0]);
        $product = $this->product('cheap', 1_000_000);

        $html = $this->renderer()->renderBlock([
            'id' => 'ib3', 'type' => 'module:installment_badge', 'props' => ['product_id' => $product->id],
        ]);
        $this->assertStringContainsString('خرید اقساطی برای این محصول در دسترس نیست', $html);
    }

    // ── Eligible: badge style shows a real, correctly-calculated monthly amount ─

    public function test_eligible_product_badge_style_shows_the_real_calculated_monthly_amount(): void
    {
        $plan = $this->plan(['period_months' => 6, 'down_payment_percent' => 20, 'monthly_interest_rate' => 0]);
        $product = $this->product('eligible', 6_000_000);

        $html = $this->renderer()->renderBlock([
            'id' => 'ib4', 'type' => 'module:installment_badge', 'props' => ['product_id' => $product->id, 'style' => 'badge'],
        ]);

        $expectedCalc = $plan->calculate(6_000_000);
        $this->assertStringContainsString('خرید اقساطی از', $html);
        $this->assertStringContainsString(CurrencyHelper::formatPrice($expectedCalc['monthly_payment']), $html);
        $this->assertStringContainsString(route('installments.apply', ['product_id' => $product->id]), $html);
    }

    // ── Eligible: full style lists every real eligible plan with correct figures ─

    public function test_eligible_product_full_style_lists_every_real_eligible_plan(): void
    {
        $planA = $this->plan(['name' => 'سه ماهه', 'period_months' => 3, 'down_payment_percent' => 30]);
        $planB = $this->plan(['name' => 'دوازده ماهه', 'period_months' => 12, 'down_payment_percent' => 10]);
        $product = $this->product('full-style', 12_000_000);

        $html = $this->renderer()->renderBlock([
            'id' => 'ib5', 'type' => 'module:installment_badge', 'props' => ['product_id' => $product->id, 'style' => 'full'],
        ]);

        $calcA = $planA->calculate(12_000_000);
        $calcB = $planB->calculate(12_000_000);

        $this->assertStringContainsString('سه ماهه', $html);
        $this->assertStringContainsString(CurrencyHelper::formatPrice($calcA['monthly_payment']), $html);
        $this->assertStringContainsString('دوازده ماهه', $html);
        $this->assertStringContainsString(CurrencyHelper::formatPrice($calcB['monthly_payment']), $html);
    }

    // ── Only genuinely eligible plans appear — never every plan regardless of range ─

    public function test_only_eligible_plans_appear_ineligible_plans_are_excluded(): void
    {
        $this->plan(['name' => 'مناسب', 'min_amount' => 0, 'max_amount' => 0]);
        $this->plan(['name' => 'نامناسب', 'min_amount' => 50_000_000, 'max_amount' => 0]);
        $product = $this->product('mixed-eligibility', 2_000_000);

        $html = $this->renderer()->renderBlock([
            'id' => 'ib6', 'type' => 'module:installment_badge', 'props' => ['product_id' => $product->id, 'style' => 'full'],
        ]);

        $this->assertStringContainsString('مناسب', $html);
        $this->assertStringNotContainsString('نامناسب', $html);
    }

    // ── Disabled: shop not enabled ──────────────────────────────────────────

    public function test_renders_nothing_when_shop_is_disabled(): void
    {
        License::query()->delete();
        License::query()->create([
            'license_key' => 'INST-NOSHOP-' . uniqid(),
            'domain' => request()->getHost(),
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => [],
        ]);
        $this->plan();
        $product = $this->product('noshop', 2_000_000);

        $html = $this->renderer()->renderBlock([
            'id' => 'ib7', 'type' => 'module:installment_badge', 'props' => ['product_id' => $product->id],
        ]);
        $this->assertSame('', trim($html));
    }

    // ── Full chain: authored via canvas-save-blocks reaches the live public homepage ─

    public function test_authored_via_save_reaches_the_live_public_homepage(): void
    {
        $plan = $this->plan(['period_months' => 6, 'down_payment_percent' => 20]);
        $product = $this->product('live-e2e', 6_000_000);

        \App\Models\Setting::set('active_canvas_theme_data', json_encode([
            'pages' => ['home' => ['is_home' => true, 'blocks' => []]],
        ]), 'theme');

        $admin = \App\Models\User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->putJson(route('admin.theme-design.canvas-save-blocks'), [
            'page_key' => 'home',
            'blocks' => [[
                'id' => 'blk_installment', 'type' => 'module:installment_badge',
                'props' => ['product_id' => $product->id, 'style' => 'badge'],
            ]],
        ])->assertOk();

        $this->actingAs($admin)->postJson(route('admin.theme-design.canvas-publish'))->assertOk();

        $response = $this->get(route('home'));
        $response->assertOk();
        $expectedCalc = $plan->calculate(6_000_000);
        $response->assertSee(CurrencyHelper::formatPrice($expectedCalc['monthly_payment']), false);
        $response->assertSee(route('installments.apply', ['product_id' => $product->id]), false);
    }
}

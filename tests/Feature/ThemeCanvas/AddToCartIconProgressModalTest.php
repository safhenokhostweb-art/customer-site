<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\License;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BLD-04/05/06/08/10 (Full Product Commercial Audit, TASK 1/3): confirms,
 * on customer-site specifically, that (a) the shared-package fixes for
 * icon/progress_bar/modal propagate correctly through ThemeCanvasRenderer's
 * own dispatch/container chain, (b) the new host-local add_to_cart renderer
 * works against real Product/cart data, and (c) `products` (a duplicate of
 * module:shop_product_grid) is no longer exposed at all.
 */
class AddToCartIconProgressModalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        License::query()->create([
            'license_key' => 'CART-' . uniqid(),
            'domain' => request()->getHost(),
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => ['shop'],
        ]);
    }

    private function renderer(): \App\Services\ThemeCanvas\ThemeCanvasRenderer
    {
        $renderer = app(\App\Services\ThemeCanvas\ThemeCanvasRenderer::class);
        $renderer->setThemeData(['pages' => []]);
        return $renderer;
    }

    public function test_products_is_no_longer_a_known_registered_type(): void
    {
        $this->assertArrayNotHasKey('products', \App\Services\ThemeCanvas\BlockRegistry::all());
    }

    public function test_icon_progress_bar_modal_render_correctly_through_theme_canvas_renderer(): void
    {
        $iconHtml = $this->renderer()->renderBlock(['id' => 'i1', 'type' => 'icon', 'props' => ['name' => '🔒', 'size' => 'small']]);
        $this->assertStringContainsString('🔒', $iconHtml);

        $barHtml = $this->renderer()->renderBlock(['id' => 'p1', 'type' => 'progress_bar', 'props' => ['value' => 42, 'label' => 'رضایت مشتری']]);
        $this->assertStringContainsString('42%', $barHtml);

        $modalHtml = $this->renderer()->renderBlock([
            'id' => 'm1', 'type' => 'modal', 'props' => ['trigger_text' => 'اطلاعات'],
            'blocks' => [['id' => 'b1', 'type' => 'text', 'props' => ['content' => 'محتوای واقعی مودال']]],
        ]);
        $this->assertStringContainsString('اطلاعات', $modalHtml);
        $this->assertStringContainsString('محتوای واقعی مودال', $modalHtml);
    }

    public function test_add_to_cart_renders_a_real_form_for_a_simple_product(): void
    {
        $product = Product::create([
            'title' => 'محصول ساده', 'slug' => 'simple-product', 'price' => 250000,
            'status' => Product::STATUS_PUBLISHED, 'order' => 0,
        ]);

        $html = $this->renderer()->renderBlock([
            'id' => 'atc1', 'type' => 'add_to_cart',
            'props' => ['product_id' => $product->id, 'text' => 'خرید کنید'],
        ]);

        $this->assertStringContainsString('خرید کنید', $html);
        $this->assertStringContainsString(route('shop.cart.add'), $html);
        $this->assertStringContainsString((string) $product->id, $html);
        $this->assertStringContainsString('<form', $html);
    }

    public function test_add_to_cart_with_missing_product_fails_safe(): void
    {
        $html = $this->renderer()->renderBlock([
            'id' => 'atc2', 'type' => 'add_to_cart', 'props' => ['product_id' => 999999],
        ]);

        $this->assertStringNotContainsString('<form', $html);
        $this->assertStringContainsString('یافت نشد', $html);
    }

    public function test_add_to_cart_with_no_product_selected_shows_empty_state_not_a_broken_form(): void
    {
        $html = $this->renderer()->renderBlock(['id' => 'atc3', 'type' => 'add_to_cart', 'props' => []]);
        $this->assertStringNotContainsString('<form', $html);
    }

    // ── BLD-09 / MOD-04: language_switcher ───────────────────────────────────

    public function test_language_switcher_renders_the_real_canonical_component(): void
    {
        $html = $this->renderer()->renderBlock(['id' => 'ls1', 'type' => 'language_switcher', 'props' => []]);

        $this->assertNotSame('', trim($html));
        $this->assertStringContainsString(route('lang.switch', 'fa'), $html);
        $this->assertStringContainsString(route('lang.switch', 'en'), $html);
        $this->assertStringContainsString('فارسی', $html);
        $this->assertStringContainsString('English', $html);
    }

    public function test_language_switcher_link_actually_switches_the_session_locale(): void
    {
        $this->get(route('lang.switch', 'en'));
        $this->assertSame('en', session('site_locale'));
    }

    // ── Placeholder re-evaluation: team (de-exposed), price_box (completed) ──

    public function test_team_is_no_longer_a_known_registered_type(): void
    {
        $this->assertArrayNotHasKey('team', \App\Services\ThemeCanvas\BlockRegistry::all());
        // team_cards (the real, working equivalent) must remain untouched.
        $this->assertArrayHasKey('team_cards', \App\Services\ThemeCanvas\BlockRegistry::all());
    }

    public function test_price_box_shows_the_real_current_price_for_a_selected_product(): void
    {
        $product = Product::create([
            'title' => 'محصول قیمتی', 'slug' => 'priced-product', 'price' => 500000,
            'status' => Product::STATUS_PUBLISHED, 'order' => 0,
        ]);

        $html = $this->renderer()->renderBlock(['id' => 'pb1', 'type' => 'price_box', 'props' => ['product_id' => $product->id]]);

        $this->assertStringNotContainsString('پس از انتخاب محصول', $html);
        $this->assertStringNotContainsString('پس از اتصال به فروشگاه', $html);
        $this->assertStringContainsString('tb-price-box-current', $html);
    }

    public function test_price_box_shows_struck_through_original_price_when_on_sale(): void
    {
        $product = Product::create([
            'title' => 'محصول تخفیف‌دار', 'slug' => 'sale-product', 'price' => 1000000, 'sale_price' => 750000,
            'status' => Product::STATUS_PUBLISHED, 'order' => 0,
        ]);

        $html = $this->renderer()->renderBlock(['id' => 'pb2', 'type' => 'price_box', 'props' => ['product_id' => $product->id]]);

        $this->assertStringContainsString('tb-price-box-original', $html);
        $this->assertStringContainsString('line-through', $html);
    }

    public function test_price_box_with_missing_product_fails_safe(): void
    {
        $html = $this->renderer()->renderBlock(['id' => 'pb3', 'type' => 'price_box', 'props' => ['product_id' => 999999]]);
        $this->assertStringContainsString('یافت نشد', $html);
    }

    public function test_price_box_with_no_product_selected_shows_honest_empty_state(): void
    {
        $html = $this->renderer()->renderBlock(['id' => 'pb4', 'type' => 'price_box', 'props' => []]);
        $this->assertStringContainsString('انتخاب نشده', $html);
    }
}

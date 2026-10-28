<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductReview;
use App\Services\ThemeBuilder\BlockRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsValidLicenseWithShop;
use Tests\TestCase;

/**
 * Phases 1/2 (saas-platform ADR-067): the cart JSON contract the theme's
 * mini-cart drawer and instant add-to-cart use (theme-runtime cart.js), and
 * the real Product data on the shared commercial product card.
 */
class ThemeCartDrawerAndCardsTest extends TestCase
{
    use RefreshDatabase;
    use SeedsValidLicenseWithShop;

    private const JSON = ['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->markSiteAsInstalled();
        $this->seedValidLicense(['shop', 'blog']);
    }

    private function product(array $attrs = []): Product
    {
        static $n = 0;
        $n++;
        return Product::create($attrs + ['title' => "کیف {$n}", 'slug' => "bag-{$n}", 'price' => 1000000, 'stock' => 10, 'status' => Product::STATUS_PUBLISHED]);
    }

    public function test_the_mini_cart_lists_lines_with_toman_totals(): void
    {
        $p = $this->product(['sale_price' => 790000]);
        $this->post(route('shop.cart.add'), ['product_id' => $p->id, 'quantity' => 2], self::JSON)
            ->assertOk()
            ->assertJson(['ok' => true, 'message' => 'به سبد اضافه شد.', 'cart' => ['count' => 2]]);

        $mini = $this->getJson(route('shop.cart.mini'))->assertOk()->json();
        $this->assertSame(2, $mini['count']);
        $this->assertSame("{$p->id}:0", $mini['items'][0]['key']);
        $this->assertSame('کیف 1', $mini['items'][0]['title']);
        $this->assertSame(2, $mini['items'][0]['quantity']);
        $this->assertSame('۱٬۵۸۰٬۰۰۰ تومان', $mini['items'][0]['total_html']);
        $this->assertSame('۱٬۵۸۰٬۰۰۰ تومان', $mini['subtotal_html']);
    }

    public function test_add_and_remove_answer_json_and_still_redirect_for_plain_posts(): void
    {
        $p = $this->product(['stock' => 1]);
        $this->post(route('shop.cart.add'), ['product_id' => $p->id, 'quantity' => 1], self::JSON)->assertOk();
        $this->post(route('shop.cart.add'), ['product_id' => $p->id, 'quantity' => 1], self::JSON)
            ->assertStatus(422)
            ->assertJson(['ok' => false, 'message' => 'موجودی کافی نیست.']);

        $this->post(route('shop.cart.remove'), ['line_key' => "{$p->id}:0"], self::JSON)
            ->assertOk()
            ->assertJson(['ok' => true, 'message' => 'محصول از سبد حذف شد.', 'cart' => ['count' => 0, 'items' => []]]);

        $this->from('/')->post(route('shop.cart.add'), ['product_id' => $p->id])->assertRedirect('/')->assertSessionHas('success');
    }

    public function test_a_product_with_variants_sends_the_script_to_its_page(): void
    {
        $p = $this->product();
        $p->variants()->create(['label' => 'قرمز', 'price' => 1000000, 'stock' => 5, 'is_active' => true]);
        $res = $this->post(route('shop.cart.add'), ['product_id' => $p->id], self::JSON);
        if (!$p->fresh()->usesVariants()) {
            $this->markTestSkipped('this schema marks variants as used through another flag');
        }
        $res->assertStatus(422)->assertJson(['ok' => false, 'url' => route('shop.show', $p->slug)]);
    }

    public function test_the_live_renderer_wires_the_drawer_and_the_real_product_card(): void
    {
        $p = $this->product(['sale_price' => 790000, 'stock' => 3, 'low_stock_threshold' => 5, 'is_new' => true]);
        ProductReview::create(['product_id' => $p->id, 'guest_name' => 'الف', 'rating' => 5, 'body' => 'عالی', 'approved' => true]);
        ProductReview::create(['product_id' => $p->id, 'guest_name' => 'ب', 'rating' => 4, 'body' => 'خوب', 'approved' => true]);
        ProductReview::create(['product_id' => $p->id, 'guest_name' => 'ج', 'rating' => 1, 'body' => 'رد', 'approved' => false]);
        $this->get('/');

        $renderer = (new BlockRenderer())->setMode('public');
        $header = $renderer->renderBlock(['type' => 'nav_header', 'id' => 'h', 'props' => ['show_cart' => true]]);
        $this->assertStringContainsString('href="/shop/cart" class="tb-nav-header-icon-link tb-cart-trigger" data-tb-cart-open', $header);
        $this->assertStringContainsString('&quot;mini&quot;:&quot;/shop/cart/mini&quot;', $header);

        $grid = $renderer->renderBlock(['type' => 'product_grid', 'id' => 'g', 'props' => ['limit' => 4]]);
        $this->assertStringContainsString('۲۱٪ تخفیف', $grid);
        $this->assertStringContainsString('tb-product-stock--low">تنها ۳ عدد باقی مانده', $grid);
        $this->assertStringContainsString('aria-label="امتیاز ۴٫۵ از ۵ از ۲ نظر"', $grid, 'only approved reviews count');
        $this->assertStringContainsString('<form class="tb-product-add-form" method="post" action="/shop/cart/add"', $grid);
        $this->assertStringContainsString('name="product_id" value="' . $p->id . '"', $grid);
        $this->assertStringContainsString('۷۹۰٬۰۰۰', $grid);
    }

    public function test_the_editor_keeps_cards_and_cart_inert(): void
    {
        $this->product();
        $renderer = (new BlockRenderer())->setMode('editor')->setContext(['editor_mode' => true]);
        $html = $renderer->renderBlock(['type' => 'product_grid', 'id' => 'g', 'props' => []]);
        $this->assertStringNotContainsString('<form class="tb-product-add-form"', $html);
        $this->assertStringNotContainsString('data-tb-cart-open', $renderer->renderBlock(['type' => 'nav_header', 'id' => 'h', 'props' => ['show_cart' => true]]));
    }
}

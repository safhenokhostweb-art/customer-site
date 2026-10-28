<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductReview;
use App\Services\ThemeCanvas\BlockRegistry as HostRegistry;
use App\Services\ThemeCanvas\CanvasStructuredData;
use App\Services\ThemeCanvas\ThemeCanvasRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsValidLicenseWithShop;
use Tests\TestCase;

/**
 * Phase 3 (saas-platform ADR-068): the drawer's quantity stepper contract
 * (JSON from shop.cart.update, unit amounts in the mini-cart), the shared
 * 30-day «پرفروش» ordering behind module:shop_product_grid, the derived
 * product badges on real products, and FAQPage JSON-LD for
 * module:faq_dynamic.
 */
class ThemePhase3CommerceTest extends TestCase
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
        return Product::create($attrs + ['title' => "محصول {$n}", 'slug' => "p3-{$n}", 'price' => 1000000, 'stock' => 10, 'status' => Product::STATUS_PUBLISHED]);
    }

    private function sell(Product $product, float $total, string $status = 'completed', ?string $when = null): void
    {
        static $n = 0;
        $n++;
        $at = $when ?? now()->toDateTimeString();
        $orderId = DB::table('orders')->insertGetId([
            'number' => "P3-{$n}", 'customer_name' => 'خریدار', 'customer_mobile' => '09120000000',
            'subtotal' => $total, 'total' => $total, 'status' => $status, 'created_at' => $at, 'updated_at' => $at,
        ]);
        DB::table('order_items')->insert([
            'order_id' => $orderId, 'product_id' => $product->id, 'product_title' => $product->title,
            'price' => $total, 'quantity' => 1, 'total' => $total, 'created_at' => $at, 'updated_at' => $at,
        ]);
    }

    private function live(): ThemeCanvasRenderer
    {
        $this->get('/'); // a request, so routes and the session exist
        return app(ThemeCanvasRenderer::class)->setMode('public');
    }

    // ── Drawer: stepper ───────────────────────────────────────────────

    public function test_update_answers_json_with_the_fresh_cart_and_still_redirects_plain_posts(): void
    {
        $p = $this->product(['sale_price' => 790000, 'stock' => 3]);
        $this->post(route('shop.cart.add'), ['product_id' => $p->id, 'quantity' => 1], self::JSON)->assertOk();
        $key = "{$p->id}:0";

        $this->post(route('shop.cart.update'), ['line_key' => $key, 'quantity' => 3], self::JSON)
            ->assertOk()
            ->assertJson(['ok' => true, 'message' => 'سبد به‌روزرسانی شد.', 'cart' => [
                'count' => 3,
                'subtotal_html' => '۲٬۳۷۰٬۰۰۰ تومان',
                'items' => [['key' => $key, 'quantity' => 3, 'total_html' => '۲٬۳۷۰٬۰۰۰ تومان']],
            ]]);

        $this->post(route('shop.cart.update'), ['line_key' => $key, 'quantity' => 4], self::JSON)
            ->assertStatus(422)
            ->assertJson(['ok' => false, 'message' => 'تعداد درخواستی از موجودی بیشتر است.']);
        $this->post(route('shop.cart.update'), ['line_key' => 'nope', 'quantity' => 1], self::JSON)
            ->assertStatus(422)->assertJson(['ok' => false, 'message' => 'ردیف سبد یافت نشد.']);

        $this->post(route('shop.cart.update'), ['line_key' => $key, 'quantity' => 0], self::JSON)
            ->assertOk()->assertJson(['ok' => true, 'cart' => ['count' => 0, 'items' => []]]);

        $this->post(route('shop.cart.add'), ['product_id' => $p->id], self::JSON)->assertOk();
        $this->from('/shop/cart')->post(route('shop.cart.update'), ['line_key' => $key, 'quantity' => 2])
            ->assertRedirect('/shop/cart')->assertSessionHas('success', 'سبد به‌روزرسانی شد.');
        $this->from('/shop/cart')->post(route('shop.cart.update'), ['line_key' => $key, 'quantity' => 9])
            ->assertRedirect('/shop/cart')->assertSessionHas('error');
    }

    public function test_the_mini_cart_carries_unit_amounts_for_instant_totals(): void
    {
        $a = $this->product(['sale_price' => 790000]);
        $b = $this->product(['price' => 1250000]);
        $this->post(route('shop.cart.add'), ['product_id' => $a->id, 'quantity' => 2], self::JSON)->assertOk();
        $this->post(route('shop.cart.add'), ['product_id' => $b->id], self::JSON)->assertOk();

        $mini = $this->getJson(route('shop.cart.mini'))->assertOk()->json();
        $this->assertSame('', $mini['money_suffix_html'], 'no currency reform: no faded zeros');
        $this->assertSame('تومان', $mini['unit']);
        $this->assertEquals(790000, $mini['items'][0]['unit_amount']);
        $this->assertEquals(1250000, $mini['items'][1]['unit_amount']);
        $this->assertSame('۲٬۸۳۰٬۰۰۰ تومان', $mini['subtotal_html']);
    }

    public function test_the_live_cart_config_offers_the_update_endpoint(): void
    {
        $html = $this->live()->renderBlock(['type' => 'nav_header', 'id' => 'h', 'props' => ['show_cart' => true]]);
        $this->assertStringContainsString('&quot;update&quot;:&quot;/shop/cart/update&quot;', $html);
    }

    // ── Bestselling ───────────────────────────────────────────────────

    public function test_the_host_offers_bestselling_again_with_no_patch_left(): void
    {
        $this->assertSame([], HostRegistry::HOST_PATCHES);
        $this->assertSame(['recent', 'featured', 'bestselling'], array_keys(HostRegistry::all()['module:shop_product_grid']['schema']['source']['options']));
    }

    public function test_bestselling_orders_by_30_day_completed_revenue(): void
    {
        $low = $this->product(['title' => 'کم‌فروش']);
        $top = $this->product(['title' => 'پرفروش‌ترین']);
        $old = $this->product(['title' => 'فروش قدیمی']);
        $pending = $this->product(['title' => 'سفارش باز']);
        $this->product(['title' => 'بدون فروش']);
        $this->sell($low, 100000);
        $this->sell($top, 900000);
        $this->sell($top, 50000);
        $this->sell($old, 5000000, 'completed', now()->subDays(31)->toDateTimeString());
        $this->sell($pending, 5000000, 'pending');

        $html = $this->live()->renderBlock(['type' => 'module:shop_product_grid', 'id' => 'g', 'props' => ['source' => 'bestselling', 'limit' => 8]]);
        preg_match_all('#<h3 class="tb-product-title">([^<]+)</h3>#u', $html, $m);
        $this->assertSame(['پرفروش‌ترین', 'کم‌فروش'], $m[1]);
        $this->assertStringContainsString('class="tb-product-card', $html, 'the shared commercial card');
    }

    public function test_bestselling_with_no_sales_says_so(): void
    {
        $this->product();
        $html = $this->live()->renderBlock(['type' => 'module:shop_product_grid', 'id' => 'g', 'props' => ['source' => 'bestselling']]);
        $this->assertStringContainsString('در ۳۰ روز گذشته هنوز فروشی ثبت نشده است.', $html);
        $this->assertStringNotContainsString('tb-product-card', $html);
    }

    public function test_recent_and_featured_keep_their_queries(): void
    {
        $this->product(['title' => 'قدیمی', 'is_new' => true]);
        $this->product(['title' => 'تازه']);
        $r = $this->live();
        preg_match_all('#<h3 class="tb-product-title">([^<]+)</h3>#u', $r->renderBlock(['type' => 'module:shop_product_grid', 'id' => 'g', 'props' => ['source' => 'recent']]), $recent);
        $this->assertSame(['تازه', 'قدیمی'], $recent[1]);
        preg_match_all('#<h3 class="tb-product-title">([^<]+)</h3>#u', $r->renderBlock(['type' => 'module:shop_product_grid', 'id' => 'g', 'props' => ['source' => 'featured']]), $featured);
        $this->assertSame(['قدیمی'], $featured[1]);
    }

    // ── Badges on real products ───────────────────────────────────────

    public function test_real_products_get_discount_top_rated_and_new_overlays(): void
    {
        $p = $this->product(['sale_price' => 790000, 'is_new' => true]);
        foreach ([5, 5, 4, 5, 4] as $i => $rating) {
            ProductReview::create(['product_id' => $p->id, 'guest_name' => "م{$i}", 'rating' => $rating, 'body' => 'خوب', 'approved' => true]);
        }
        $this->product(['title' => 'ساده']);

        $html = $this->live()->renderBlock(['type' => 'module:shop_product_slider', 'id' => 's', 'props' => ['source' => 'recent']]);
        $this->assertMatchesRegularExpression('#<div class="tb-product-ribbons">.*۲۱٪ تخفیف.*پرامتیاز.*جدید.*?</div>#su', $html);
        $this->assertSame(1, substr_count($html, 'tb-product-ribbons'), 'the plain product carries no overlay');
    }

    // ── FAQ ───────────────────────────────────────────────────────────

    public function test_faq_dynamic_contributes_only_its_visible_items_to_faq_page(): void
    {
        $blocks = [
            ['id' => 'f1', 'type' => 'faq', 'props' => ['items' => [['question' => 'الف؟', 'answer' => 'یک']]]],
            ['id' => 'f2', 'type' => 'module:faq_dynamic', 'props' => ['limit' => 2, 'items' => [
                ['question' => 'ب؟', 'answer' => 'دو'],
                ['title' => 'ج؟', 'content' => 'سه'],
                ['question' => 'پنهان؟', 'answer' => 'بیش از سقف'],
            ]]],
        ];
        $graphs = app(CanvasStructuredData::class)->for($blocks, ['title' => 'صفحه', 'canonical' => 'https://example.test/']);
        $faq = collect($graphs)->firstWhere('@type', 'FAQPage');
        $this->assertSame(['الف؟', 'ب؟', 'ج؟'], array_column($faq['mainEntity'], 'name'), 'one FAQPage for the page, beyond-limit items left out');
        $this->assertSame('سه', $faq['mainEntity'][2]['acceptedAnswer']['text']);

        $html = $this->live()->renderBlock($blocks[1]);
        $this->assertStringContainsString('class="tb-section tb-faq"', $html, 'the accordion style is the canonical FAQ');
        $this->assertStringContainsString('ج؟', $html);
        $this->assertStringNotContainsString('پنهان؟', $html);
    }

    public function test_the_canonical_faq_keeps_the_host_empty_hint_and_rich_answers(): void
    {
        $r = $this->live();
        $this->assertStringContainsString('سوالات متداول از پنل مدیریت قابل ویرایش است.', $r->renderBlock(['type' => 'faq', 'id' => 'e', 'props' => ['items' => []]]));
        $html = $r->renderBlock(['type' => 'faq', 'id' => 'q', 'props' => ['items' => [['question' => 'Q', 'answer' => '<p>با <strong>پست</strong></p>']]]]);
        $this->assertStringContainsString('<p>با <strong>پست</strong></p>', $html);
    }
}

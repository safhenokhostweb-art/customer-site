<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use App\Services\ThemeCanvas\ThemeCanvasRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsValidLicenseWithShop;
use Tests\TestCase;

/**
 * Phase 3b (saas-platform ADR-069): module:reviews_grid on the shared review
 * card with real data — verified buyers, Jalali dates, the Persian rating
 * breakdown and schema.org microdata — and the bento grid's live product
 * cell.
 */
class ThemePhase3bReviewsBentoTest extends TestCase
{
    use RefreshDatabase;
    use SeedsValidLicenseWithShop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markSiteAsInstalled();
        $this->seedValidLicense(['shop', 'blog', 'reviews']);
    }

    private function product(array $attrs = []): Product
    {
        static $n = 0;
        $n++;
        return Product::create($attrs + ['title' => "محصول {$n}", 'slug' => "p3b-{$n}", 'price' => 1000000, 'stock' => 10, 'status' => Product::STATUS_PUBLISHED]);
    }

    private function buy(User $user, Product $product, string $status): void
    {
        $orderId = DB::table('orders')->insertGetId([
            'number' => 'P3B-' . uniqid(), 'user_id' => $user->id, 'customer_name' => 'خریدار', 'customer_mobile' => '09120000000',
            'subtotal' => 1000, 'total' => 1000, 'status' => $status, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('order_items')->insert([
            'order_id' => $orderId, 'product_id' => $product->id, 'product_title' => $product->title,
            'price' => 1000, 'quantity' => 1, 'total' => 1000, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function review(Product $product, int $rating, array $attrs = []): ProductReview
    {
        $review = ProductReview::create($attrs + ['product_id' => $product->id, 'guest_name' => 'مهمان', 'rating' => $rating, 'body' => 'نظر ' . $rating, 'approved' => true]);
        $review->forceFill(['created_at' => '2026-10-24 10:00:00'])->save();
        return $review;
    }

    private function live(): ThemeCanvasRenderer
    {
        $this->get('/');
        return app(ThemeCanvasRenderer::class)->setMode('public');
    }

    public function test_reviews_show_verified_buyers_jalali_dates_and_standalone_microdata(): void
    {
        $bag = $this->product(['title' => 'کیف چرم']);
        $buyer = User::factory()->create(['name' => 'سارا']);
        $browser = User::factory()->create(['name' => 'علی']);
        $canceller = User::factory()->create(['name' => 'مینا']);
        $this->buy($buyer, $bag, 'shipped');
        $this->buy($canceller, $bag, 'cancelled');
        $this->review($bag, 5, ['user_id' => $buyer->id, 'guest_name' => null, 'title' => 'عالی']);
        $this->review($bag, 4, ['user_id' => $browser->id, 'guest_name' => null]);
        $this->review($bag, 3, ['user_id' => $canceller->id, 'guest_name' => null]);
        $this->review($bag, 1, ['approved' => false]);

        $html = $this->live()->renderBlock(['type' => 'module:reviews_grid', 'id' => 'rv', 'props' => ['limit' => 6, 'show_summary' => true]]);

        $this->assertSame(3, substr_count($html, 'class="tb-testimonial-card tb-review-card" itemscope itemtype="https://schema.org/Review"'), 'approved reviews only');
        $this->assertSame(1, substr_count($html, 'خریدار تاییدشده'), 'only a paid order verifies; a cancelled one does not');
        $this->assertMatchesRegularExpression('#<span itemprop="name">سارا</span>.*?خریدار تاییدشده#su', $html);
        $this->assertSame(3, substr_count($html, '<time class="tb-review-date" datetime="2026-10-24" itemprop="datePublished">۲ آبان ۱۴۰۵</time>'));
        $this->assertStringContainsString('<span itemprop="itemReviewed" itemscope itemtype="https://schema.org/Product"><meta itemprop="name" content="کیف چرم">', $html);
        $this->assertStringContainsString('<h3 class="tb-review-title" itemprop="name">عالی</h3>', $html);
        $this->assertStringContainsString('<span class="tb-rating-average">۴٫۰</span>', $html, 'store breakdown over approved reviews');
        $this->assertStringNotContainsString('aggregateRating', $html, 'a mixed list rates no single item');
    }

    public function test_one_products_reviews_sit_in_its_product_scope_with_aggregate_rating(): void
    {
        $bag = $this->product(['title' => 'کیف چرم']);
        $other = $this->product(['title' => 'کفش']);
        foreach ([5, 5, 4] as $r) {
            $this->review($bag, $r);
        }
        $this->review($other, 1);

        $html = $this->live()->renderBlock(['type' => 'module:reviews_grid', 'id' => 'rv', 'props' => ['product_id' => $bag->id, 'limit' => 2]]);

        $this->assertStringContainsString('<div itemscope itemtype="https://schema.org/Product"><meta itemprop="name" content="کیف چرم"><link itemprop="url" href="' . route('shop.show', $bag->slug) . '">', $html);
        $this->assertStringContainsString('<meta itemprop="ratingValue" content="4.7"><meta itemprop="reviewCount" content="3">', $html, 'the aggregate covers all its reviews, not just the shown ones');
        $this->assertSame(2, substr_count($html, 'itemprop="review" itemscope itemtype="https://schema.org/Review"'));
        $this->assertStringNotContainsString('itemReviewed', $html);
        $this->assertStringNotContainsString('کفش', $html);
    }

    public function test_minimal_style_and_no_summary_keep_microdata_off(): void
    {
        $this->review($this->product(), 5);
        $r = $this->live();
        $minimal = $r->renderBlock(['type' => 'module:reviews_grid', 'id' => 'rv', 'props' => ['style' => 'minimal']]);
        $this->assertStringNotContainsString('itemscope', $minimal);
        $this->assertStringNotContainsString('tb-rating-summary', $minimal);
        $plain = $r->renderBlock(['type' => 'module:reviews_grid', 'id' => 'rv', 'props' => ['show_summary' => false, 'show_avatar' => false]]);
        $this->assertStringNotContainsString('tb-rating-summary', $plain);
        $this->assertStringNotContainsString('tb-review-avatar', $plain);
    }

    public function test_bento_product_cell_shows_the_chosen_or_newest_real_product(): void
    {
        $chosen = $this->product(['title' => 'انتخابی', 'sale_price' => 790000]);
        $this->product(['title' => 'تازه‌ترین']);
        $r = $this->live();

        $html = $r->renderBlock(['type' => 'bento_grid', 'id' => 'bg', 'props' => ['items' => [
            ['kind' => 'product', 'title' => 'پیشنهاد', 'product_id' => $chosen->id],
            ['kind' => 'product', 'product_id' => 0],
            ['kind' => 'product', 'product_id' => 999999],
        ]]]);
        $this->assertStringContainsString('<h3 class="tb-product-title">انتخابی</h3>', $html);
        $this->assertStringContainsString('۲۱٪ تخفیف', $html);
        $this->assertStringContainsString('<h3 class="tb-product-title">تازه‌ترین</h3>', $html);
        $this->assertStringContainsString('محصولی برای نمایش پیدا نشد.', $html);
        $this->assertStringContainsString('<form class="tb-product-add-form"', $html, 'the live cell has instant add-to-cart');
        $this->assertStringNotContainsString('نمونه؛ در سایت', $html, 'no Builder sample on the live site');
    }
}

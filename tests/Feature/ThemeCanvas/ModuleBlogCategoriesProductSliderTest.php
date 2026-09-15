<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\Category;
use App\Models\License;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BLD-02 / BLD-03 (Full Product Commercial Audit, TASK 1/3):
 *
 * BLD-02: `module:blog_categories` was dispatched into the blog-POSTS
 * renderer (querying Post, never Category, ignoring its own `style` field).
 * Now has its own renderer querying real Category records.
 *
 * BLD-03: `module:shop_product_slider` was dispatched into the static grid
 * renderer — `autoplay` had no effect and it never produced carousel
 * markup. Now routes through the real carousel runtime.
 */
class ModuleBlogCategoriesProductSliderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        License::query()->create([
            'license_key' => 'MODTEST-' . uniqid(),
            'domain' => request()->getHost(),
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => ['shop', 'blog'],
        ]);
    }

    private function renderer(): \App\Services\ThemeCanvas\ThemeCanvasRenderer
    {
        $renderer = app(\App\Services\ThemeCanvas\ThemeCanvasRenderer::class);
        $renderer->setThemeData(['pages' => []]);
        return $renderer;
    }

    private function publishedPost(string $title, string $slug, int $categoryId): Post
    {
        return Post::create([
            'title' => $title, 'slug' => $slug, 'body' => 'x', 'category_id' => $categoryId,
            'user_id' => User::factory()->create()->id,
            'is_published' => true, 'editorial_status' => Post::EDITORIAL_PUBLISHED, 'published_at' => now(),
        ]);
    }

    // ── BLD-02: blog_categories ─────────────────────────────────────────────

    public function test_blog_categories_renders_real_categories_with_published_post_counts(): void
    {
        $withPosts = Category::create(['name' => 'اخبار', 'slug' => 'news', 'order' => 1]);
        $empty = Category::create(['name' => 'خالی', 'slug' => 'empty-cat', 'order' => 2]);

        $this->publishedPost('خبر یک', 'news-1', $withPosts->id);

        $html = $this->renderer()->renderBlock([
            'id' => 'bc1', 'type' => 'module:blog_categories',
            'props' => ['title' => 'دسته‌ها', 'style' => 'cards', 'limit' => 8],
        ]);

        $this->assertStringContainsString('اخبار', $html);
        $this->assertStringContainsString('1 مطلب', $html);
        $this->assertStringNotContainsString('خالی', $html, 'categories with zero published posts must not be listed');
    }

    public function test_blog_categories_style_field_actually_changes_the_markup(): void
    {
        $category = Category::create(['name' => 'راهنما', 'slug' => 'guide', 'order' => 1]);
        $this->publishedPost('راهنمای یک', 'guide-1', $category->id);

        $cardsHtml = $this->renderer()->renderBlock(['id' => 'bc2', 'type' => 'module:blog_categories', 'props' => ['style' => 'cards']]);
        $listHtml = $this->renderer()->renderBlock(['id' => 'bc3', 'type' => 'module:blog_categories', 'props' => ['style' => 'list']]);
        $badgesHtml = $this->renderer()->renderBlock(['id' => 'bc4', 'type' => 'module:blog_categories', 'props' => ['style' => 'badges']]);

        $this->assertStringContainsString('tb-blog-category-card', $cardsHtml);
        $this->assertStringContainsString('tb-blog-category-list-item', $listHtml);
        $this->assertStringContainsString('<ul', $listHtml);
        $this->assertStringContainsString('tb-blog-category-badge', $badgesHtml);
    }

    public function test_blog_categories_with_no_qualifying_categories_shows_empty_state_not_invalid(): void
    {
        $html = $this->renderer()->renderBlock(['id' => 'bc5', 'type' => 'module:blog_categories', 'props' => []]);
        $this->assertStringNotContainsString('[invalid]', $html);
        $this->assertStringContainsString('دسته‌بندی', $html);
    }

    // ── BLD-03: shop_product_slider ──────────────────────────────────────────

    private function product(string $title, string $slug, bool $isNew = false): Product
    {
        return Product::create([
            'title' => $title, 'slug' => $slug, 'price' => 100000,
            'status' => Product::STATUS_PUBLISHED, 'is_new' => $isNew, 'order' => 0,
        ]);
    }

    public function test_shop_product_slider_produces_real_carousel_markup_not_a_static_grid(): void
    {
        $this->product('محصول یک', 'p1', true);
        $this->product('محصول دو', 'p2', true);

        $html = $this->renderer()->renderBlock([
            'id' => 'ps1', 'type' => 'module:shop_product_slider',
            'props' => ['title' => 'پیشنهادی', 'limit' => 6, 'source' => 'featured', 'autoplay' => true, 'interval' => 4000],
        ]);

        $this->assertStringContainsString('محصول یک', $html);
        $this->assertStringContainsString('محصول دو', $html);
        $this->assertStringContainsString('tb-carousel', $html, 'must render through the real carousel runtime, not a plain grid');
        $this->assertStringContainsString('data-autoplay="true"', $html);
        $this->assertStringContainsString('data-interval="4000"', $html);
        $this->assertStringNotContainsString('tb-grid tb-grid-cols', $html, 'must not fall back to the static grid markup');
    }

    public function test_shop_product_slider_autoplay_false_has_real_effect(): void
    {
        $this->product('محصول الف', 'pa', true);
        $this->product('محصول ب', 'pb', true);

        $html = $this->renderer()->renderBlock([
            'id' => 'ps2', 'type' => 'module:shop_product_slider',
            'props' => ['autoplay' => false],
        ]);

        $this->assertStringContainsString('data-autoplay="false"', $html);
    }

    public function test_shop_product_slider_source_featured_uses_is_new_filter(): void
    {
        $this->product('نو', 'new-1', true);
        $this->product('قدیمی', 'old-1', false);

        $html = $this->renderer()->renderBlock([
            'id' => 'ps3', 'type' => 'module:shop_product_slider',
            'props' => ['source' => 'featured'],
        ]);

        $this->assertStringContainsString('نو', $html);
        $this->assertStringNotContainsString('قدیمی', $html);
    }
}

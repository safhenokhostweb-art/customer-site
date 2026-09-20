<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\License;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\ThemeBuilder\ElementSchemaRegistry;
use App\Services\ThemeCanvas\BlockRegistry;
use App\Services\ThemeCanvas\ThemeCanvasRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Universal Multipurpose Slider Overhaul — real Product/ProductCategory/
 * Post-bound rendering. Covers the host-specific overrides
 * (ThemeCanvasRenderer::renderProductSlider()/renderCategorySlider(), and
 * the shared-with-other-blocks App\Services\ThemeBuilder\BlockRenderer::
 * renderBlogPosts()) that a shared, database-free package cannot provide —
 * these are the "real data instead of the admin-preview demo fixture"
 * half of the parity contract; SliderRenderersTest (saas-platform) covers
 * the demo-fixture half.
 */
class SliderRealDataRenderingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        License::create([
            'license_key' => 'SLIDER-TEST-' . uniqid(),
            'domain' => request()->getHost(),
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => ['shop', 'blog'],
        ]);
    }

    private function renderer(): ThemeCanvasRenderer
    {
        return new ThemeCanvasRenderer();
    }

    private function product(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'title' => 'محصول تست',
            'slug' => 'test-product-' . uniqid(),
            'price' => 500000,
            'stock' => 10,
            'status' => Product::STATUS_PUBLISHED,
        ], $overrides));
    }

    private function category(array $overrides = []): ProductCategory
    {
        return ProductCategory::create(array_merge([
            'name' => 'دسته تست',
            'slug' => 'test-category-' . uniqid(),
        ], $overrides));
    }

    private function makePost(array $overrides = []): Post
    {
        return Post::create(array_merge([
            'title' => 'مقاله تست',
            'slug' => 'test-post-' . uniqid(),
            'body' => 'متن کامل مقاله برای تست.',
            'user_id' => \App\Models\User::factory()->create()->id,
            'is_published' => true,
            'editorial_status' => Post::EDITORIAL_PUBLISHED,
            'published_at' => now()->subDay(),
        ], $overrides));
    }

    // ── Product Slider ─────────────────────────────────────────────────

    public function test_product_slider_renders_real_products_not_the_demo_fixture(): void
    {
        $p = $this->product(['title' => 'محصول واقعی من']);

        $html = $this->renderer()->renderBlock(['type' => 'product_slider', 'id' => 'ps1', 'props' => ['columns' => 3]]);

        self::assertStringContainsString('محصول واقعی من', $html);
        self::assertStringNotContainsString('کیف چرم دست‌دوز', $html, 'must not fall back to the canonical demo fixture once real products exist');
    }

    public function test_product_slider_excludes_a_deleted_product_without_crashing(): void
    {
        $kept = $this->product(['title' => 'محصول باقی‌مانده']);
        $deletedId = $this->product(['title' => 'محصول حذف‌شده'])->id;
        Product::destroy($deletedId);

        $html = $this->renderer()->renderBlock(['type' => 'product_slider', 'id' => 'ps2', 'props' => ['source' => 'manual', 'manual_ids' => [$kept->id, $deletedId]]]);

        self::assertStringContainsString('محصول باقی‌مانده', $html);
        self::assertStringNotContainsString('محصول حذف‌شده', $html);
    }

    public function test_product_slider_shows_discount_badge_and_safe_image_fallback(): void
    {
        $this->product(['title' => 'محصول تخفیف‌دار', 'price' => 1000000, 'sale_price' => 750000]);

        $html = $this->renderer()->renderBlock(['type' => 'product_slider', 'id' => 'ps3', 'props' => []]);

        self::assertStringContainsString('تخفیف', $html);
        // No image column set — must use the safe fallback, never an empty src.
        self::assertStringNotContainsString('src=""', $html);
        self::assertStringContainsString('tb-img-fallback', $html);
    }

    public function test_product_slider_returns_empty_for_a_shop_disabled_site(): void
    {
        \App\Models\License::query()->delete();
        $this->product();

        $html = $this->renderer()->renderBlock(['type' => 'product_slider', 'id' => 'ps4', 'props' => []]);

        self::assertSame('', $html);
    }

    // ── Category Slider ────────────────────────────────────────────────

    public function test_category_slider_renders_real_categories_with_real_product_counts(): void
    {
        $cat = $this->category(['name' => 'دسته واقعی من']);
        $this->product(['category_id' => $cat->id]);
        $this->product(['category_id' => $cat->id]);

        $html = $this->renderer()->renderBlock(['type' => 'category_slider', 'id' => 'cs1', 'props' => []]);

        self::assertStringContainsString('دسته واقعی من', $html);
        self::assertStringContainsString('2', $html);
        self::assertStringNotContainsString('پوشاک', $html, 'must not fall back to the canonical demo fixture once real categories exist');
    }

    public function test_category_slider_grid_layout_renders_real_data_too(): void
    {
        $this->category(['name' => 'دسته گرید']);

        $html = $this->renderer()->renderBlock(['type' => 'category_slider', 'id' => 'cs2', 'props' => ['layout' => 'grid']]);

        self::assertStringContainsString('دسته گرید', $html);
        self::assertStringContainsString('tb-grid-cols-', $html);
    }

    // ── Blog Slider (blog_posts, layout=slider) ──────────────────────────

    public function test_blog_posts_slider_layout_renders_real_posts_with_carousel(): void
    {
        $this->makePost(['title' => 'مقاله واقعی من']);

        $html = $this->renderer()->renderBlock(['type' => 'blog_posts', 'id' => 'bp1', 'props' => ['layout' => 'slider']]);

        self::assertStringContainsString('مقاله واقعی من', $html);
        self::assertStringContainsString('tb-blog-slider', $html);
        // This is the real defect this phase closes: previously there was
        // no host override at all, so this exact placeholder text rendered
        // permanently regardless of real content. Guard against regressing
        // back to a placeholder-only render on customer-site.
        self::assertStringNotContainsString('در این‌جا نمایش داده می‌شود', $html);
    }

    public function test_blog_posts_grid_layout_still_works_unchanged(): void
    {
        $this->makePost(['title' => 'مقاله گرید']);

        $html = $this->renderer()->renderBlock(['type' => 'blog_posts', 'id' => 'bp2', 'props' => ['layout' => 'grid']]);

        self::assertStringContainsString('مقاله گرید', $html);
        self::assertStringContainsString('tb-blog-grid', $html);
    }

    public function test_blog_posts_excludes_an_unpublished_post(): void
    {
        $this->makePost(['title' => 'مقاله منتشرشده']);
        $this->makePost(['title' => 'مقاله پیش‌نویس', 'is_published' => false]);

        $html = $this->renderer()->renderBlock(['type' => 'blog_posts', 'id' => 'bp3', 'props' => ['layout' => 'slider']]);

        self::assertStringContainsString('مقاله منتشرشده', $html);
        self::assertStringNotContainsString('مقاله پیش‌نویس', $html);
    }

    // ── Editor → Save → Reload → Preview → Customer Render, end-to-end ──
    //
    // A real browser-driven Canvas-UI E2E (dragging a block, filling its
    // form, clicking Save) is not exercised here — see the final report's
    // "remaining items" for that gap. This proves the part of the pipeline
    // that is actually at risk from this phase's schema changes: that the
    // customer-site Editor registry's own defaultProps (App\Services\
    // ThemeCanvas\BlockRegistry — what "Save" actually persists for a
    // freshly-inserted block) survive real schema VALIDATION (what
    // "Reload"/re-render re-checks every time) and then genuinely render
    // (what "Preview" and "Customer Render" both call) — the same
    // literal data at every step, not three different fixtures.
    public function test_every_sliders_editor_default_props_pass_validation_and_render(): void
    {
        $this->category(['name' => 'دسته‌بندی پیش‌فرض']);
        $this->product(['title' => 'محصول پیش‌فرض']);
        $this->makePost(['title' => 'مقاله پیش‌فرض']);

        $registry = BlockRegistry::all();
        $validator = app(ElementSchemaRegistry::class);
        $renderer = $this->renderer();

        foreach (['slider', 'product_slider', 'category_slider', 'testimonials_slider', 'blog_posts', 'client_logos'] as $type) {
            self::assertArrayHasKey($type, $registry, "the customer editor picker must know about '{$type}'");

            $block = ['type' => $type, 'id' => 'e2e-' . $type, 'props' => $registry[$type]['defaultProps']];

            self::assertTrue(
                $validator->validate($block),
                "'{$type}' editor defaultProps failed schema validation: " . json_encode($validator->getLastErrors())
            );

            $html = $renderer->renderBlock($block);
            self::assertNotSame('', trim($html), "'{$type}' rendered empty output from its own editor default data");
        }
    }
}

<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\Category;
use App\Models\License;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\User;
use Database\Seeders\UniversalMultipurposeQaDemoCommerceContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Commercial Productization fase-5 — real Product/Category/Post demo
 * content, seeded through real Eloquent models (never a raw INSERT), so
 * Product Detail and Article Detail (the real, official routes) have
 * genuine data instead of only ever showing their honest empty-state.
 */
class UniversalMultipurposeQaDemoCommerceContentSeederTest extends TestCase
{
    use RefreshDatabase;

    private bool $createdInstallLock = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (! \App\Support\Installation::isLockPresent()) {
            $this->createdInstallLock = \App\Support\Installation::writeLockFile();
        }
    }

    protected function tearDown(): void
    {
        if ($this->createdInstallLock) {
            @unlink(\App\Support\Installation::lockPath());
        }

        parent::tearDown();
    }

    public function test_seeds_at_least_six_products_three_categories_and_six_posts_with_real_content(): void
    {
        $usersBeforeSeeding = User::count();
        $this->seed(UniversalMultipurposeQaDemoCommerceContentSeeder::class);

        self::assertGreaterThanOrEqual(3, ProductCategory::count());
        self::assertGreaterThanOrEqual(6, Product::count());
        self::assertGreaterThanOrEqual(1, Category::count());
        self::assertGreaterThanOrEqual(6, Post::count());
        self::assertSame(1, License::count());
        // Some earlier suite tests commit rows past their rollback, so count
        // what this seeder adds rather than the absolute table size.
        self::assertSame(1, User::count() - $usersBeforeSeeding, 'the seeder creates exactly one author');

        foreach (Product::all() as $product) {
            self::assertNotSame('', trim($product->title));
            self::assertNotSame('', trim((string) $product->description));
            self::assertNotSame('', trim((string) $product->image), "{$product->slug} must have a real main image");
            self::assertGreaterThan(0, $product->price);
            self::assertNotNull($product->category_id);
        }

        // At least one product each with a real discount badge, a real
        // "new" badge, and a real out-of-stock state — proving the demo
        // content is genuinely varied, not six copies of the same shape.
        self::assertGreaterThan(0, Product::whereNotNull('sale_price')->count());
        self::assertGreaterThan(0, Product::where('is_new', true)->count());
        self::assertGreaterThan(0, Product::where('stock', 0)->count());

        foreach (Post::all() as $post) {
            self::assertNotSame('', trim($post->title));
            self::assertNotSame('', trim((string) $post->excerpt));
            self::assertGreaterThan(200, strlen((string) $post->body), "{$post->slug} must have a real, non-trivial body");
            self::assertStringContainsString('<h2', (string) $post->body, 'real headings for the article-detail table of contents');
            self::assertNotSame('', trim((string) $post->featured_image));
            self::assertNotEmpty($post->gallery_images);
            self::assertNotNull($post->user_id);
            self::assertNotNull($post->category_id);
            self::assertNotNull($post->published_at);
            self::assertTrue($post->is_published);
        }
    }

    public function test_product_images_are_real_decodable_jpegs(): void
    {
        $this->seed(UniversalMultipurposeQaDemoCommerceContentSeeder::class);

        $disk = app(\App\Services\MediaStorage::class)->diskNameFor('public');
        $product = Product::whereNotNull('image')->first();
        self::assertNotNull($product);

        $full = \Illuminate\Support\Facades\Storage::disk($disk)->path($product->image);
        $info = @getimagesize($full);
        self::assertNotFalse($info, 'the product main image must be a real, decodable image, not a stub file');
        self::assertSame('image/jpeg', $info['mime']);

        self::assertGreaterThanOrEqual(1, ProductImage::where('product_id', $product->id)->count(), 'a real product gallery, not just a single main image');
    }

    public function test_reseeding_is_idempotent_and_never_duplicates_rows(): void
    {
        $this->seed(UniversalMultipurposeQaDemoCommerceContentSeeder::class);
        $counts1 = [
            Product::count(), ProductCategory::count(), Post::count(),
            Category::count(), License::count(), User::count(), ProductImage::count(),
        ];

        $this->seed(UniversalMultipurposeQaDemoCommerceContentSeeder::class);
        $counts2 = [
            Product::count(), ProductCategory::count(), Post::count(),
            Category::count(), License::count(), User::count(), ProductImage::count(),
        ];

        self::assertSame($counts1, $counts2, 're-running the seeder must converge to the same state, never duplicate rows');
    }

    public function test_shop_and_blog_module_are_genuinely_enabled_by_the_seeded_license_not_a_config_bypass(): void
    {
        $this->seed(UniversalMultipurposeQaDemoCommerceContentSeeder::class);

        self::assertTrue(\App\Helpers\SiteHelper::shopEnabled());
        self::assertTrue(\App\Helpers\SiteHelper::blogEnabled());
    }

    public function test_a_real_product_detail_page_renders_with_real_data_not_an_empty_state(): void
    {
        $this->seed(UniversalMultipurposeQaDemoCommerceContentSeeder::class);
        $product = Product::first();

        $response = $this->get(route('shop.show', $product->slug));

        $response->assertOk();
        $response->assertSee($product->title);
        $response->assertDontSee('محصولی یافت نشد');
    }

    public function test_a_real_article_detail_page_renders_with_real_data_not_an_empty_state(): void
    {
        $this->seed(UniversalMultipurposeQaDemoCommerceContentSeeder::class);
        $post = Post::first();

        $response = $this->get(route('blog.show', $post->slug));

        $response->assertOk();
        $response->assertSee($post->title);
        $response->assertSee($post->user->name);
    }
}

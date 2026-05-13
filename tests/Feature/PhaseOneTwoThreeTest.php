<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsValidLicenseWithShop;
use Tests\TestCase;

class PhaseOneTwoThreeTest extends TestCase
{
    use RefreshDatabase, SeedsValidLicenseWithShop;

    /**
     * Wave 7.0a — Same host-aware license bootstrap as `ShopModuleFeatureTest`,
     * extended with the `blog` module so blog/shop public routes can render.
     * `config('site.owner_site')` is preserved for backward-compatibility with
     * any test in this file that relies on owner-site middleware bypass; with
     * a valid license in place it is effectively redundant but harmless.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->markSiteAsInstalled();
        config(['site.owner_site' => true]);
        $this->seedValidLicense(['blog', 'shop']);
    }

    public function test_shop_index_with_category_filter(): void
    {
        $cat = ProductCategory::create(['name' => 'کتاب', 'slug' => 'book', 'order' => 0]);
        Product::create([
            'title' => 'کتاب تست',
            'slug' => 'test-book',
            'price' => 1000,
            'category_id' => $cat->id,
            'status' => Product::STATUS_PUBLISHED,
        ]);
        $response = $this->get(route('shop.index', ['category' => 'book']));
        $response->assertStatus(200);
        $response->assertSee('کتاب تست');
    }

    public function test_shop_search(): void
    {
        Product::create(['title' => 'محصول ویژه', 'slug' => 'special', 'price' => 5000, 'status' => Product::STATUS_PUBLISHED]);
        $response = $this->get(route('shop.index', ['q' => 'ویژه']));
        $response->assertStatus(200);
        $response->assertSee('محصول ویژه');
    }

    public function test_blog_search(): void
    {
        $user = User::factory()->create();
        Post::create([
            'title' => 'مطلب تست جستجو',
            'slug' => 'test-search',
            'body' => 'متن',
            'user_id' => $user->id,
            'is_published' => true,
            'editorial_status' => Post::EDITORIAL_PUBLISHED,
            'published_at' => now(),
        ]);
        $response = $this->get(route('blog.index', ['q' => 'جستجو']));
        $response->assertStatus(200);
        $response->assertSee('مطلب تست جستجو');
    }

    public function test_blog_show_displays_related_posts_and_reading_time(): void
    {
        $user = User::factory()->create();
        $main = Post::create([
            'title' => 'مطلب اصلی وبلاگ',
            'slug' => 'main-post',
            'body' => str_repeat('متن تست ', 300),
            'user_id' => $user->id,
            'category_id' => null,
            'is_published' => true,
            'editorial_status' => Post::EDITORIAL_PUBLISHED,
            'published_at' => now()->subDay(),
        ]);
        Post::create([
            'title' => 'مطلب مرتبط وبلاگ',
            'slug' => 'related-post',
            'body' => 'متن کوتاه',
            'user_id' => $user->id,
            'category_id' => null,
            'is_published' => true,
            'editorial_status' => Post::EDITORIAL_PUBLISHED,
            'published_at' => now()->subHours(2),
        ]);

        $response = $this->get(route('blog.show', $main->slug));
        $response->assertOk();
        $response->assertSee('مطالب مرتبط');
        $response->assertSee('مطلب مرتبط وبلاگ');
        $response->assertSee('دقیقه مطالعه');
    }

    public function test_about_us_block_shows_when_enabled(): void
    {
        Setting::set('about_us_enabled', '1');
        Setting::set('about_us_title', 'درباره ما');
        Setting::set('about_us_text', 'متن تست درباره ما');
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('درباره ما');
        $response->assertSee('متن تست درباره ما');
    }

    public function test_about_us_block_hidden_when_disabled(): void
    {
        Setting::set('about_us_enabled', '0');
        Setting::set('about_us_title', 'درباره ما');
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertDontSee('درباره ما');
    }

    public function test_theme_class_applied_to_body(): void
    {
        Setting::set('theme', 'minimal');
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('theme-minimal');
    }

    public function test_manifest_exists(): void
    {
        $path = public_path('manifest.json');
        $this->assertFileExists($path);
        $json = json_decode(file_get_contents($path), true);
        $this->assertIsArray($json);
        $this->assertArrayHasKey('name', $json);
        $this->assertStringContainsString('سایت', $json['name']);
    }

    public function test_home_has_loading_script_on_forms(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('در حال ارسال');
    }
}

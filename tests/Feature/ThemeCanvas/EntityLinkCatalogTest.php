<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Setting;
use App\Models\User;
use App\Services\ThemeBuilder\BlockRenderer;
use App\Services\ThemeCanvas\EntityLinkCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsValidLicenseWithShop;
use Tests\TestCase;

/**
 * Canvas overhaul Unit 4: one picker for every link field. A pick is stored
 * as {type: entity, entity, id} and resolved to the live permalink at every
 * render, so a slug change or unpublish shows up without touching the theme
 * ("two-way sync" with the dashboard), and only enabled modules are offered.
 */
class EntityLinkCatalogTest extends TestCase
{
    use RefreshDatabase, SeedsValidLicenseWithShop;

    private function ref(string $entity, int|string $id): array
    {
        return ['type' => 'entity', 'entity' => $entity, 'id' => $id];
    }

    private function catalog(): EntityLinkCatalog
    {
        return app(EntityLinkCatalog::class);
    }

    public function test_every_entity_type_resolves_to_its_live_permalink(): void
    {
        $this->seedValidLicense(['shop', 'blog']);
        $page = Page::create(['title' => 'درباره ما', 'slug' => 'about-us', 'body' => '<p>x</p>', 'is_published' => true]);
        $cat = ProductCategory::create(['name' => 'کفش', 'slug' => 'shoes']);
        $product = Product::create(['title' => 'کفش دویدن', 'slug' => 'running-shoe', 'price' => 1000, 'status' => Product::STATUS_PUBLISHED, 'category_id' => $cat->id]);
        $blogCat = Category::create(['name' => 'راهنما', 'slug' => 'guides']);
        $post = Post::create(['user_id' => User::factory()->create()->id, 'title' => 'راهنمای خرید', 'slug' => 'buying-guide', 'body' => '<p>x</p>', 'is_published' => true, 'editorial_status' => Post::EDITORIAL_PUBLISHED, 'published_at' => now()->subDay()]);

        $this->assertSame(route('page.show', 'about-us'), $this->catalog()->resolve($this->ref('page', $page->id)));
        $this->assertSame(route('shop.show', 'running-shoe'), $this->catalog()->resolve($this->ref('product', $product->id)));
        $this->assertSame(route('shop.index', ['category' => 'shoes']), $this->catalog()->resolve($this->ref('product_category', $cat->id)));
        $this->assertSame(route('blog.show', 'buying-guide'), $this->catalog()->resolve($this->ref('post', $post->id)));
        $this->assertSame(route('blog.category', 'guides'), $this->catalog()->resolve($this->ref('blog_category', $blogCat->id)));
        $this->assertSame(route('home'), $this->catalog()->resolve($this->ref('route', 'home')));
        $this->assertSame(route('shop.index'), $this->catalog()->resolve($this->ref('route', 'shop')));
    }

    public function test_a_slug_rename_is_followed_and_unpublished_content_resolves_to_no_link(): void
    {
        $page = Page::create(['title' => 'راهنما', 'slug' => 'guide-v1', 'body' => '<p>x</p>', 'is_published' => true]);
        $page->update(['slug' => 'guide-v2']);
        $this->assertSame(route('page.show', 'guide-v2'), app(EntityLinkCatalog::class)->resolve($this->ref('page', $page->id)));

        $page->update(['is_published' => false]);
        $this->assertSame('', app(EntityLinkCatalog::class)->resolve($this->ref('page', $page->id)), 'a fresh catalog must not resolve an unpublished page');
        $this->assertSame('', app(EntityLinkCatalog::class)->resolve($this->ref('page', 999999)));
        $this->assertSame('', app(EntityLinkCatalog::class)->resolve($this->ref('page', 'not-an-id')));
    }

    public function test_destinations_of_a_disabled_module_are_neither_offered_nor_resolved(): void
    {
        // No license: shop and blog are off (license-only modules).
        $product = Product::create(['title' => 'کفش', 'slug' => 'shoe', 'price' => 1, 'status' => Product::STATUS_PUBLISHED]);

        $this->assertSame('', $this->catalog()->resolve($this->ref('product', $product->id)));
        $this->assertSame('', $this->catalog()->resolve($this->ref('route', 'shop')));
        $entities = array_column($this->catalog()->search(''), 'entity');
        $this->assertNotContains('product', $entities);
        $this->assertContains('route', $entities, 'the home page is always offered');
    }

    public function test_the_search_endpoint_is_admin_only_and_returns_live_results(): void
    {
        $this->seedValidLicense(['shop', 'menu']);
        Page::create(['title' => 'تماس با ما', 'slug' => 'contact-us', 'body' => '<p>x</p>', 'is_published' => true]);
        Product::create(['title' => 'هدفون بی‌سیم', 'slug' => 'wireless-headphones', 'price' => 5, 'status' => Product::STATUS_PUBLISHED]);
        $url = route('admin.theme-design.canvas-link-entities');

        $this->get($url . '?q=تماس')->assertRedirect();

        $admin = User::factory()->create(['is_admin' => true]);
        $response = $this->actingAs($admin)->getJson($url . '?q=' . urlencode('تماس'))->assertOk();
        $this->assertSame('page', $response->json('results.0.entity'));
        $this->assertSame(route('page.show', 'contact-us'), $response->json('results.0.url'));
        $this->assertArrayHasKey('product', $response->json('entities'));

        $only = $this->actingAs($admin)->getJson($url . '?entity=product')->assertOk()->json('results');
        $this->assertSame(['product'], array_values(array_unique(array_column($only, 'entity'))));
        $this->assertSame(route('shop.show', 'wireless-headphones'), $only[0]['url']);

        $this->actingAs($admin)->getJson($url . '?entity=nope')->assertStatus(422);
    }

    public function test_an_entity_picked_inside_nav_links_renders_as_the_live_permalink(): void
    {
        $page = Page::create(['title' => 'خدمات', 'slug' => 'services', 'body' => '<p>x</p>', 'is_published' => true]);

        $html = app(BlockRenderer::class)->renderBlock(['id' => 'hdr', 'type' => 'nav_header', 'props' => [
            'logo_text' => 'برند', 'nav_links' => [
                ['label' => 'خدمات', 'url' => $this->ref('page', $page->id) + ['label' => 'خدمات']],
            ],
        ]]);

        $this->assertStringContainsString('href="' . route('page.show', 'services') . '"', $html);
        $this->assertStringNotContainsString('tb-placeholder', $html);
    }

    public function test_a_picked_entity_on_a_public_page_follows_the_dashboard(): void
    {
        $page = Page::create(['title' => 'قیمت‌ها', 'slug' => 'pricing-v1', 'body' => '<p>x</p>', 'is_published' => true]);
        Setting::set('active_canvas_theme_data', json_encode([
            'name' => 'Test', 'slug' => 'test',
            'header_blocks' => [], 'footer_blocks' => [], 'global_styles' => [],
            'pages' => [['key' => 'home', 'title' => 'Home', 'blocks' => [
                ['id' => 'b1', 'type' => 'button', 'props' => ['text' => 'قیمت‌ها', 'url' => $this->ref('page', $page->id)]],
            ]]],
        ], JSON_UNESCAPED_UNICODE), 'theme');
        Setting::set('active_canvas_theme_name', 'Test', 'theme');

        $this->get('/')->assertOk()->assertSee(route('page.show', 'pricing-v1'), false);

        $page->update(['slug' => 'pricing-v2']);
        $this->get('/')->assertOk()->assertSee(route('page.show', 'pricing-v2'), false)->assertDontSee(route('page.show', 'pricing-v1'), false);
    }
}

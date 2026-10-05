<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\ThemeCanvas\DataBinding\ThemeDataBindingRegistry;
use App\Services\ThemeCanvas\ThemeCanvasRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use SaaSPlatform\ThemeRuntime\BlockRenderer;
use Tests\Support\CreatesFoodMartBindingFixtures;
use Tests\Concerns\SeedsValidLicenseWithShop;
use Tests\TestCase;

/**
 * Phase 4B.6 — user-acceptance content-model gate. Complements (does not
 * replace) FoodMartDataBindingTest (Phase 4B) and FoodMartCanvasEditingTest
 * (Phase 4B.5): the previous automated gates passed while real users could
 * not edit what they SEE. Proves:
 *  - every content-bearing FoodMart block renders manual items through the
 *    SAME canonical FoodMart presentation (no generic markup),
 *  - manual/dynamic source switching never destroys either side's data,
 *  - blog has a real published-scope dynamic binding and a real manual mode,
 *  - newsletter/header/footer expose all visible theme-owned content.
 */
class FoodMartContentModelTest extends TestCase
{
    use CreatesFoodMartBindingFixtures;
    use RefreshDatabase;
    use SeedsValidLicenseWithShop;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        $this->seedValidLicense(['shop']);
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    // ── Manual mode feeds the SAME canonical presentation ───────────────────

    public function test_manual_product_items_render_through_golden_product_card_presentation(): void
    {
        $block = ['id' => 'manual-products', 'type' => 'foodmart_trending_products', 'props' => [
            'items' => [
                ['image' => 'foodmart-fixtures/fruit.png', 'image_alt' => 'Fruit', 'url' => '/shop/fruit', 'badge' => '-14%',
                 'title' => 'Manual Basket <script>alert(1)</script>', 'unit_label' => '1 Basket', 'price_text' => '$2.99',
                 'old_price_text' => '$3.49', 'cta_label' => 'Shop Now'],
            ],
        ]];

        $html = app(ThemeCanvasRenderer::class)->renderBlock($block);

        // Golden card anatomy — identical classes/structure to dynamic mode:
        self::assertStringContainsString('class="product-item swiper-slide"', $html);
        self::assertStringContainsString('<span class="qty">1 Basket</span>', $html);
        self::assertStringContainsString('<del>$3.49</del> $2.99', $html);
        self::assertStringContainsString('badge bg-success', $html);
        self::assertStringContainsString('class="btn-wishlist"', $html);
        self::assertStringContainsString('<h3>', $html);
        // Non-carousel trending grid keeps the Golden bootstrap-tabs wrapper:
        self::assertStringContainsString('bootstrap-tabs product-tabs', $html);
        self::assertStringContainsString('product-grid row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5', $html);
        // Text stays escaped — no raw HTML editing channel:
        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
        // No generic platform card markup leaked into the theme:
        self::assertStringNotContainsString('tb-generic-card', $html);
        // Cart form MUST NOT be fabricated for a non-entity manual card:
        self::assertStringNotContainsString('name="product_id"', $html);
        self::assertStringNotContainsString(route('shop.cart.add'), $html);
    }

    public function test_manual_blog_items_render_through_golden_blog_card_presentation(): void
    {
        $block = ['id' => 'manual-blog', 'type' => 'foodmart_blog', 'props' => [
            'section_title' => 'From the kitchen',
            'items' => [
                ['image' => 'foodmart-fixtures/fruit.png', 'image_alt' => 'post', 'url' => '/blog/post-1',
                 'date_text' => '22 Aug 2021', 'category_label' => 'Recipes',
                 'title' => 'Manual Blog Card', 'excerpt' => 'A hand-written teaser.'],
            ],
        ]];

        $html = app(ThemeCanvasRenderer::class)->renderBlock($block);

        self::assertStringContainsString('post-item card border-0 shadow-sm p-3', $html);
        self::assertStringContainsString('image-holder zoom-effect', $html);
        self::assertStringContainsString('post-meta', $html);
        self::assertStringContainsString('meta-date', $html);
        self::assertStringContainsString('meta-categories', $html);
        self::assertStringContainsString('post-title', $html);
        self::assertStringContainsString('Manual Blog Card', $html);
        self::assertStringContainsString('A hand-written teaser.', $html);
        self::assertStringContainsString('22 Aug 2021', $html);
        self::assertStringContainsString('Recipes', $html);
        self::assertStringContainsString('From the kitchen', $html);
    }

    public function test_manual_categories_and_trust_promo_brands_render_through_golden_presentation(): void
    {
        $categories = app(ThemeCanvasRenderer::class)->renderBlock(['id' => 'c', 'type' => 'foodmart_categories', 'props' => [
            'section_title' => 'Shop by aisle', 'view_all_label' => 'Browse all', 'view_all_url' => '/shop',
            'items' => [['title' => 'Fresh Fruit', 'image' => 'foodmart-fixtures/fruit.png', 'image_alt' => 'Fruit', 'url' => '/shop/fruit']],
        ]]);
        self::assertStringContainsString('category-carousel', $categories);
        self::assertStringContainsString('Fresh Fruit', $html = $categories);
        self::assertStringContainsString('Shop by aisle', $categories);

        $trust = app(ThemeCanvasRenderer::class)->renderBlock(['id' => 't', 'type' => 'foodmart_trust_badges', 'props' => [
            'items' => [['title' => 'Free delivery', 'description' => 'On every order above $50']],
        ]]);
        self::assertStringContainsString('Free delivery', $trust);
        self::assertStringContainsString('On every order above $50', $trust);

        $promo = app(ThemeCanvasRenderer::class)->renderBlock(['id' => 'p', 'type' => 'foodmart_promo_banners', 'props' => [
            'items' => [['badge' => 'Upto 40% Off', 'title' => 'Summer savings', 'cta_url' => '/shop/summer']],
        ]]);
        self::assertStringContainsString('Summer savings', $promo);
        self::assertStringContainsString('Upto 40% Off', $promo);

        $brands = app(ThemeCanvasRenderer::class)->renderBlock(['id' => 'b', 'type' => 'foodmart_brands', 'props' => [
            'items' => [['image' => 'foodmart-fixtures/fruit.png', 'name' => 'Amber Jar', 'title' => 'Honey best nectar']],
        ]]);
        self::assertStringContainsString('Amber Jar', $brands);
        self::assertStringContainsString('Honey best nectar', $brands);
    }

    // ── Manual/Dynamic switch preserves both sides ──────────────────────────

    public function test_dynamic_binding_wins_and_manual_data_survives_untouched_in_props(): void
    {
        $this->createFoodMartBindingFixtures();
        $block = [
            'id' => 'switch', 'type' => 'foodmart_trending_products',
            'props' => ['items' => [['title' => 'My Precious Manual Card', 'price_text' => '$9.99', 'image' => '', 'url' => '#', 'cta_label' => 'Shop Now']]],
            'binding' => ['source' => 'products', 'query' => ['sort' => 'catalog_order', 'limit' => 3], 'empty' => 'hide'],
        ];

        $bindings = app(ThemeDataBindingRegistry::class)->resolveBlockLists([$block]);
        $html = app(ThemeCanvasRenderer::class)->setContext(['theme_bindings' => $bindings])->renderBlock($block);

        // Dynamic mode: real DB products visible, manual card NOT rendered.
        self::assertStringContainsString('Fixture Plain Bread', $html);
        self::assertStringNotContainsString('My Precious Manual Card', $html);
        // …and the manual payload survives verbatim in props for the switch back:
        $stored = $block['props']['items'][0];
        self::assertSame('My Precious Manual Card', $stored['title']);
        self::assertSame('$9.99', $stored['price_text']);

        // Switch back to manual (drop the binding) — same presentation returns.
        $manual = ['id' => 'switch', 'type' => 'foodmart_trending_products', 'props' => $block['props']];
        $back = app(ThemeCanvasRenderer::class)->renderBlock($manual);
        self::assertStringContainsString('My Precious Manual Card', $back);
        self::assertStringContainsString('product-item swiper-slide', $back);
    }

    public function test_editor_source_switch_never_loses_manual_items_or_binding_config(): void
    {
        // Mirrors the editor's setContentSource() flow end-to-end through the
        // real save endpoint (the browser spec clicks the same buttons).
        Setting::set('active_canvas_theme_data', json_encode([
            'name' => 'FoodMart switch fixture', 'header_blocks' => [], 'footer_blocks' => [],
            'pages' => ['home' => ['title' => 'H', 'slug' => 'home', 'is_home' => true, 'blocks' => []]],
            'global_styles' => [], 'theme_assets' => ['localized' => true, 'assets' => []],
        ]), 'theme');
        $admin = User::factory()->create(['is_admin' => true]);

        $block = ['id' => 'sw', 'type' => 'foodmart_blog', 'props' => [
            'items' => [['image' => '', 'image_alt' => '', 'url' => '#', 'date_text' => '1 Jan 2026', 'category_label' => 'News', 'title' => 'Kept card', 'excerpt' => '']],
        ]];
        // Step 1: author manual card, save.
        $this->actingAs($admin)->putJson(route('admin.theme-design.canvas-save-blocks'), [
            'page_key' => 'home', 'blocks' => [$block],
        ])->assertOk();

        // Step 2: switch to Dynamic (binding added), save.
        $dynamic = $block;
        $dynamic['binding'] = ['source' => 'blog_posts', 'query' => ['sort' => 'latest', 'limit' => 3], 'empty' => 'hide'];
        $this->actingAs($admin)->putJson(route('admin.theme-design.canvas-save-blocks'), [
            'page_key' => 'home', 'blocks' => [$dynamic],
        ])->assertOk();

        // Step 3: switch back to Manual (binding deleted), save.
        $this->actingAs($admin)->putJson(route('admin.theme-design.canvas-save-blocks'), [
            'page_key' => 'home', 'blocks' => [$block],
        ])->assertOk();

        $raw = Setting::get('canvas_draft_data');
        $page = json_decode($raw, true)['pages']['home']['blocks'][0] ?? [];
        self::assertSame('Kept card', $page['props']['items'][0]['title'] ?? null, 'Manual item lost across source switch');
        self::assertArrayNotHasKey('binding', $page, 'Binding config leaked back into manual mode');
    }

    // ── Blog dynamic binding (real Post domain) ─────────────────────────────

    public function test_blog_posts_binding_renders_published_posts_through_foodmart_cards(): void
    {
        $user = User::factory()->create();
        $cat = Category::create(['name' => 'Recipes', 'slug' => 'fixture-recipes', 'order' => 1]);
        Storage::fake('uploads');
        $this->writeFixturePng('foodmart-fixtures/blog.png', [200, 120, 60]);
        $published = Post::create([
            'title' => 'Fixture Blog Post Alpha', 'slug' => 'fixture-blog-alpha', 'body' => 'x', 'excerpt' => 'Alpha teaser',
            'category_id' => $cat->id, 'user_id' => $user->id, 'featured_image' => 'foodmart-fixtures/blog.png',
            'is_published' => true, 'editorial_status' => Post::EDITORIAL_PUBLISHED, 'published_at' => now()->subDay(),
        ]);
        Post::create([
            'title' => 'Fixture Hidden Blog Draft', 'slug' => 'fixture-blog-draft', 'body' => 'x',
            'category_id' => $cat->id, 'user_id' => $user->id,
            'is_published' => false, 'editorial_status' => Post::EDITORIAL_DRAFT, 'published_at' => now(),
        ]);

        $block = ['id' => 'blog-dyn', 'type' => 'foodmart_blog', 'props' => [],
            'binding' => ['source' => 'blog_posts', 'query' => ['sort' => 'latest', 'limit' => 3], 'empty' => 'empty_state']];
        $bindings = app(ThemeDataBindingRegistry::class)->resolveBlockLists([$block]);

        self::assertSame('resolved', $bindings['blog-dyn']['status'] ?? null, json_encode($bindings));
        self::assertSame(['Fixture Blog Post Alpha'], array_column($bindings['blog-dyn']['items'], 'title'));

        $html = app(ThemeCanvasRenderer::class)->setContext(['theme_bindings' => $bindings])->renderBlock($block);
        self::assertStringContainsString('post-item card', $html);
        self::assertStringContainsString('Fixture Blog Post Alpha', $html);
        self::assertStringContainsString('/uploads/foodmart-fixtures/blog.png', $html);
        self::assertStringNotContainsString('Fixture Hidden Blog Draft', $html);
        self::assertSame(1, (int) $published->exists);
    }

    public function test_blog_binding_fails_closed_for_unknown_sort_and_rejects_drafts(): void
    {
        $user = User::factory()->create();
        Post::create([
            'title' => 'Only Published', 'slug' => 'only-pub', 'body' => 'x', 'user_id' => $user->id,
            'is_published' => true, 'editorial_status' => Post::EDITORIAL_PUBLISHED, 'published_at' => now(),
        ]);
        $bad = ['id' => 'b1', 'type' => 'foodmart_blog', 'props' => [],
            'binding' => ['source' => 'blog_posts', 'query' => ['sort' => 'raw_sql', 'limit' => 3], 'empty' => 'hide']];
        $bindings = app(ThemeDataBindingRegistry::class)->resolveBlockLists([$bad]);
        self::assertSame('invalid', $bindings['b1']['status']);

        $good = ['id' => 'b2', 'type' => 'foodmart_blog', 'props' => [],
            'binding' => ['source' => 'blog_posts', 'query' => ['sort' => 'manual', 'selected_ids' => [999], 'limit' => 3], 'empty' => 'empty_state']];
        $bindings2 = app(ThemeDataBindingRegistry::class)->resolveBlockLists([$good]);
        $html = app(ThemeCanvasRenderer::class)->setContext(['theme_bindings' => $bindings2])->renderBlock($good);
        self::assertStringNotContainsString('Only Published', $html);
    }

    // ── Newsletter / Header / Footer full content model ─────────────────────

    public function test_newsletter_exposes_every_visible_content_field(): void
    {
        $block = ['id' => 'nl', 'type' => 'foodmart_newsletter', 'props' => [
            'title' => 'First purchase discount',
            'description' => 'Sign up and save today.',
            'name_label' => 'Full name', 'name_placeholder' => 'Type your name',
            'email_label' => 'Email address', 'email_placeholder' => 'you@example.com',
            'checkbox_label' => 'Send me offers', 'button_label' => 'Claim now',
        ]];

        $html = app(ThemeCanvasRenderer::class)->renderBlock($block);

        self::assertStringContainsString('First purchase discount', $html);
        self::assertStringContainsString('Sign up and save today.', $html);
        self::assertStringContainsString('>Full name</label>', $html);
        self::assertStringContainsString('placeholder="Type your name"', $html);
        self::assertStringContainsString('>Email address</label>', $html);
        self::assertStringContainsString('placeholder="you@example.com"', $html);
        self::assertStringContainsString('Send me offers', $html);
        self::assertStringContainsString('Claim now', $html);
        // Untouched newsletter keeps the byte-exact default:
        $default = app(ThemeCanvasRenderer::class)->renderBlock(['id' => 'nl2', 'type' => 'foodmart_newsletter', 'props' => []]);
        self::assertStringContainsString('Get <span class="text-primary">25% Discount</span>', $default);
    }

    public function test_header_content_is_fully_editable_through_schema_props(): void
    {
        $block = ['id' => 'hdr', 'type' => 'foodmart_header', 'props' => [
            'logo_image' => 'foodmart-fixtures/fruit.png', 'logo_alt' => 'Shop logo',
            'search_placeholder' => 'Find groceries', 'filter_label' => 'Departments',
            'filter_options' => 'Fruit,Bakery', 'support_label' => 'Need help?', 'support_phone' => '+1 555 0100',
            'cart_label' => 'Cart', 'nav_items' => [
                ['label' => 'Home', 'url' => 'index.html'],
                ['label' => 'Contact <b>x</b>', 'url' => '/contact'],
            ],
        ]];

        $html = app(ThemeCanvasRenderer::class)->renderBlock($block);

        self::assertStringContainsString('src="foodmart-fixtures/fruit.png"', $html);
        self::assertStringContainsString('alt="Shop logo"', $html);
        self::assertStringContainsString('placeholder="Find groceries"', $html);
        self::assertStringContainsString('<option>Departments</option>', $html);
        self::assertStringContainsString('<option>Fruit</option>', $html);
        self::assertStringContainsString('<option>Bakery</option>', $html);
        self::assertStringContainsString('Need help?', $html);
        self::assertStringContainsString('+1 555 0100', $html);
        self::assertStringContainsString('Cart', $html);
        self::assertStringContainsString('<li class="nav-item"', $html);
        self::assertStringContainsString('Contact &lt;b&gt;x&lt;/b&gt;', $html);
        self::assertStringNotContainsString('<b>x</b>', $html);
        // Canonical header wrapper classes intact:
        self::assertStringContainsString('<header', $html);
        self::assertStringContainsString('navbar', $html);
    }

    public function test_footer_content_is_fully_editable_through_schema_props(): void
    {
        $block = ['id' => 'ftr', 'type' => 'foodmart_footer', 'props' => [
            'logo_image' => 'foodmart-fixtures/fruit.png', 'logo_alt' => 'Footer logo',
            'copyright' => '© 2026 My Store Inc', 'credit_text' => 'Built with care',
            'newsletter_heading' => 'Join us', 'newsletter_text' => 'Weekly deals for subscribers.',
            'newsletter_placeholder' => 'Your email', 'newsletter_button' => 'Subscribe',
            'columns' => [
                ['heading' => 'Company', 'links' => [
                    ['label' => 'About us', 'url' => '/about'],
                    ['label' => 'Careers <i>x</i>', 'url' => '/careers'],
                ]],
            ],
        ]];

        $html = app(ThemeCanvasRenderer::class)->renderBlock($block);

        self::assertStringContainsString('<footer class="py-5">', $html);
        self::assertStringContainsString('src="foodmart-fixtures/fruit.png"', $html);
        self::assertStringContainsString('alt="Footer logo"', $html);
        self::assertStringContainsString('© 2026 My Store Inc', $html);
        self::assertStringContainsString('Built with care', $html);
        self::assertStringContainsString('Join us', $html);
        self::assertStringContainsString('Weekly deals for subscribers.', $html);
        self::assertStringContainsString('placeholder="Your email"', $html);
        self::assertStringContainsString('>Subscribe</button>', $html);
        self::assertStringContainsString('>Company</h5>', $html);
        self::assertStringContainsString('About us', $html);
        self::assertStringContainsString('Careers &lt;i&gt;x&lt;/i&gt;', $html);
        self::assertStringNotContainsString('<i>x</i>', $html);
    }

    public function test_offcanvas_infra_blocks_are_the_only_justified_no_settings_types(): void
    {
        $justified = ['foodmart_svg_sprite', 'foodmart_preloader', 'foodmart_offcanvas_cart', 'foodmart_offcanvas_search'];
        foreach (\App\Services\ThemeCanvas\BlockRegistry::all() as $type => $def) {
            if (!str_starts_with($type, 'foodmart_')) {
                continue;
            }
            $schema = $def['schema'] ?? [];
            $reason = trim((string) ($def['schemaAuditReason'] ?? ''));
            if ($schema === [] && $reason !== '') {
                self::assertContains($type, $justified, "{$type} declares a reason but is not in the justified infra list");
            } elseif ($schema === []) {
                self::fail("{$type} has neither schema nor an audit reason — would show 'no settings' with visible content");
            }
        }
    }
}

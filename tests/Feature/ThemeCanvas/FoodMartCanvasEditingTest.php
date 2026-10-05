<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\ThemeCanvas\DataBinding\ThemeDataBindingRegistry;
use App\Services\ThemeCanvas\ThemeCanvasRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SaaSPlatform\ThemeRuntime\BlockRenderer;
use Tests\Support\CreatesFoodMartBindingFixtures;
use Tests\Concerns\SeedsValidLicenseWithShop;
use Tests\TestCase;

/**
 * Phase 4B.5 — Canvas real editing foundation (Products + Categories slice).
 *
 * Proves the schema-driven property panel exposes meaningful controls for
 * the first proof blocks (hero, categories, trending products), that edits
 * flow through the SAME canonical renderer into the live preview, that the
 * unbound static output stays byte-identical, and that none of it weakens
 * the Phase 4B binding security model.
 */
class FoodMartCanvasEditingTest extends TestCase
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

    public function test_proof_blocks_declare_meaningful_editable_schemas(): void
    {
        $registry = \App\Services\ThemeCanvas\BlockRegistry::all();

        $hero = $registry['foodmart_hero']['schema'] ?? [];
        self::assertSame('repeater', $hero['slides']['type'] ?? '');
        self::assertSame('image', $hero['slides']['fields']['image']['type'] ?? '');
        foreach (['label', 'title', 'description', 'cta_label', 'cta_url', 'image'] as $field) {
            self::assertArrayHasKey($field, $hero['slides']['fields']);
        }
        self::assertSame('repeater', $hero['promos']['type'] ?? '');

        foreach (['foodmart_categories', 'foodmart_trending_products'] as $type) {
            self::assertSame(
                'string',
                $registry[$type]['schema']['section_title']['type'] ?? '',
                "{$type} must expose a section_title content field"
            );
        }

        // Structure is intentionally NOT schema-declared: no raw HTML or
        // class editor may appear for any proof block.
        foreach (['foodmart_hero', 'foodmart_categories', 'foodmart_trending_products'] as $type) {
            self::assertArrayNotHasKey('html', $registry[$type]['schema'] ?? []);
            self::assertArrayNotHasKey('custom_html', $registry[$type]['schema'] ?? []);
            self::assertArrayNotHasKey('class', $registry[$type]['schema'] ?? []);
        }
    }

    public function test_hero_props_render_foodmart_presentation_with_edited_content(): void
    {
        $block = ['id' => 'hero-edit', 'type' => 'foodmart_hero', 'props' => [
            'slides' => [
                [
                    'label' => 'فصل تابستان', 'title' => 'Example <b>Title</b>',
                    'description' => 'A fresh description', 'cta_label' => 'خرید کنید',
                    'cta_url' => '/shop', 'image' => 'foodmart-fixtures/fruit.png',
                ],
            ],
            'promos' => [['badge' => '20% off', 'title' => 'Fruits & Vegetables', 'cta_url' => '/shop/category']],
        ]];

        $html = app(ThemeCanvasRenderer::class)->renderBlock($block);

        // FoodMart presentation preserved (same classes/skeleton), content from props
        self::assertStringContainsString('class="banner-ad large bg-info block-1"', $html);
        self::assertStringContainsString('class="swiper main-swiper"', $html);
        self::assertStringContainsString('فصل تابستان', $html);
        self::assertStringContainsString('Example &lt;b&gt;Title&lt;/b&gt;', $html);
        self::assertStringContainsString('A fresh description', $html);
        self::assertStringContainsString('<img src="foodmart-fixtures/fruit.png" class="img-fluid" alt="Fruit">', $html);
        self::assertStringContainsString('href="/shop"', $html);
        self::assertStringContainsString('Fruits &amp; Vegetables', $html);
        self::assertStringNotContainsString('<b>', $html);
        self::assertStringNotContainsString('Fresh Smoothie', $html);
    }

    public function test_hero_url_and_image_security_gates_fail_closed(): void
    {
        $block = ['id' => 'hero-safe', 'type' => 'foodmart_hero', 'props' => [
            'slides' => [[
                'title' => 'Safe', 'cta_url' => 'javascript:alert(1)',
                'image' => '../../.env',
            ]],
            'promos' => [['title' => 'Bad', 'cta_url' => 'javascript:alert(1)']],
        ]];

        $html = app(ThemeCanvasRenderer::class)->renderBlock($block);

        self::assertStringNotContainsString('javascript:', $html);
        self::assertStringNotContainsString('../../.env', $html);
        self::assertStringNotContainsString('href="javascript', $html);
        // Unsafe image dropped; the slide itself still renders (empty img wrapper)
        self::assertStringContainsString('Safe', $html);
    }

    public function test_trending_products_section_title_edits_both_bound_and_static_output(): void
    {
        $fixtures = $this->createFoodMartBindingFixtures();

        // Bound: section title flows through the bound renderer
        $bound = ['id' => 'trending-bound', 'type' => 'foodmart_trending_products', 'props' => ['section_title' => 'پرفروش‌های هفته'],
            'binding' => ['source' => 'products', 'query' => ['sort' => 'manual', 'selected_ids' => [$fixtures['sale']->id], 'limit' => 4]]];
        $bindings = app(ThemeDataBindingRegistry::class)->resolveBlockLists([$bound]);
        $html = app(ThemeCanvasRenderer::class)->setContext(['theme_bindings' => $bindings])->renderBlock($bound);
        self::assertStringContainsString('<h3>پرفروش‌های هفته</h3>', $html);
        self::assertStringContainsString('Fixture Sale Apple', $html);

        // Static (unbound): same title substitution on the byte-exact markup
        $static = ['id' => 'trending-static', 'type' => 'foodmart_trending_products', 'props' => ['section_title' => 'پیشنهاد ویژه']];
        $staticHtml = app(ThemeCanvasRenderer::class)->renderBlock($static);
        self::assertStringContainsString('<h3>پیشنهاد ویژه</h3>', $staticHtml);
        self::assertStringContainsString('bootstrap-tabs product-tabs', $staticHtml);
    }

    public function test_categories_section_title_edits_bound_output(): void
    {
        $this->createFoodMartBindingFixtures();

        $bound = ['id' => 'cat-bound', 'type' => 'foodmart_categories', 'props' => ['section_title' => 'خرید بر اساس دسته'],
            'binding' => ['source' => 'product_categories', 'query' => ['sort' => 'catalog_order', 'limit' => 12]]];
        $bindings = app(ThemeDataBindingRegistry::class)->resolveBlockLists([$bound]);
        $html = app(ThemeCanvasRenderer::class)->setContext(['theme_bindings' => $bindings])->renderBlock($bound);
        self::assertStringContainsString('<h2 class="section-title">خرید بر اساس دسته</h2>', $html);
        self::assertStringContainsString('category-carousel swiper', $html);
    }

    public function test_unbound_output_stays_byte_identical_without_props(): void
    {
        foreach (['foodmart_hero', 'foodmart_categories', 'foodmart_trending_products'] as $type) {
            $block = ['id' => 'same-'.$type, 'type' => $type, 'props' => []];
            $before = (new BlockRenderer())->renderBlock($block);
            $after = app(ThemeCanvasRenderer::class)->setContext(['theme_bindings' => []])->renderBlock($block);
            self::assertSame($before, $after, "{$type} unbound output drifted");
        }
    }

    public function test_default_props_reach_canonical_renderer_and_public_canvas_share_resolution(): void
    {
        $fixtures = $this->createFoodMartBindingFixtures();

        $block = ['id' => 'parity-title', 'type' => 'foodmart_trending_products',
            'props' => ['section_title' => 'محصولات داغ'],
            'binding' => ['source' => 'products', 'query' => ['sort' => 'manual', 'selected_ids' => [$fixtures['sale']->id], 'limit' => 1]]];
        $theme = [
            'name' => 'FoodMart editing fixture', 'header_blocks' => [], 'footer_blocks' => [],
            'pages' => ['home' => ['title' => 'Fixture home', 'slug' => 'home', 'is_home' => true, 'blocks' => [$block]]],
            'global_styles' => ['direction' => 'ltr', 'chrome_owner' => 'theme', 'presentation_css_owner' => 'theme'],
            'theme_assets' => ['localized' => true, 'assets' => []],
        ];
        Setting::set('active_canvas_theme_data', json_encode($theme), 'theme');
        $admin = User::factory()->create(['is_admin' => true]);

        $public = $this->get('/')->assertOk();
        $canvas = $this->actingAs($admin)->get(route('admin.theme-design.canvas-live-preview', ['page' => 'home']))->assertOk();
        $incremental = $this->postJson(route('admin.theme-design.canvas-render-block'), ['block' => $block])->assertOk();

        foreach ([$public, $canvas] as $response) {
            $response->assertSee('محصولات داغ', false);
            $response->assertSee('Fixture Sale Apple');
        }
        self::assertStringContainsString('<h3>محصولات داغ</h3>', $incremental->json('html'));
    }

    public function test_binding_entity_endpoint_is_allowlisted_and_tenant_safe(): void
    {
        $fixtures = $this->createFoodMartBindingFixtures();
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->getJson(route('admin.theme-design.canvas-binding-entities', ['source' => 'products']))->assertOk();
        $entities = $response->json('entities');
        $labels = array_column($entities, 'label');
        self::assertContains('Fixture Sale Apple', $labels);
        self::assertNotContains('Fixture Hidden Draft', $labels); // unpublished filtered by real domain rules
        foreach ($entities as $entity) {
            self::assertIsInt($entity['id']);
            self::assertNotSame('Fixture Hidden Draft', $entity['label']);
        }

        $categories = $this->actingAs($admin)->getJson(route('admin.theme-design.canvas-binding-entities', ['source' => 'product_categories']))->assertOk();
        self::assertContains('Fixture Fruit', array_column($categories->json('entities'), 'label'));

        // Unknown sources are rejected — the allowlist is the registry itself
        $this->actingAs($admin)->getJson(route('admin.theme-design.canvas-binding-entities', ['source' => 'arbitrary_model']))->assertNotFound();
        $this->actingAs($admin)->getJson(route('admin.theme-design.canvas-binding-entities', ['source' => 'users']))->assertNotFound();
    }

    public function test_edited_props_bindings_and_hero_slides_survive_save_and_reload(): void
    {
        $fixtures = $this->createFoodMartBindingFixtures();
        $admin = User::factory()->create(['is_admin' => true]);

        $block = ['id' => 'fm-edit-proof', 'type' => 'foodmart_trending_products',
            'props' => ['section_title' => 'پرفروش‌های امروز'],
            'binding' => ['source' => 'products', 'query' => ['sort' => 'manual', 'selected_ids' => [$fixtures['sale']->id], 'limit' => 1]]];
        $heroBlock = ['id' => 'fm-hero-proof', 'type' => 'foodmart_hero', 'props' => [
            'slides' => [['label' => 'تازه رسید', 'title' => 'میوه تازه', 'description' => '', 'cta_url' => '/shop', 'image' => '']]],
        ];
        $theme = [
            'name' => 'FoodMart save/reload fixture', 'header_blocks' => [], 'footer_blocks' => [],
            'pages' => ['home' => ['title' => 'Save proof', 'slug' => 'home', 'is_home' => true, 'blocks' => [$heroBlock, $block]]],
            'global_styles' => ['direction' => 'ltr', 'chrome_owner' => 'theme', 'presentation_css_owner' => 'theme'],
            'theme_assets' => ['localized' => true, 'assets' => []],
        ];
        Setting::set('active_canvas_theme_data', json_encode($theme), 'theme');

        // EDIT + SAVE through the real editor endpoint (exactly what the
        // property panel's save flow sends).
        $block['props']['section_title'] = 'پرفروش‌های هفته';
        $block['binding']['query']['selected_ids'] = [$fixtures['sale']->id, $fixtures['regular']->id];
        $this->actingAs($admin)->putJson(route('admin.theme-design.canvas-save-blocks'), [
            'page_key' => 'home',
            'blocks' => [$heroBlock, $block],
        ])->assertOk()->assertJson(['success' => true]);

        // RELOAD: the draft carries the edited title, BOTH selected IDs,
        // and the hero slides repeater verbatim.
        $draft = json_decode(Setting::get('canvas_draft_data'), true);
        $savedTrending = $draft['pages']['home']['blocks'][1];
        $savedHero = $draft['pages']['home']['blocks'][0];
        self::assertSame('پرفروش‌های هفته', $savedTrending['props']['section_title']);
        self::assertSame([$fixtures['sale']->id, $fixtures['regular']->id], $savedTrending['binding']['query']['selected_ids']);
        self::assertSame('میوه تازه', $savedHero['props']['slides'][0]['title']);
        self::assertSame('تازه رسید', $savedHero['props']['slides'][0]['label']);

        // The reloaded draft renders through the canonical renderer with
        // both edits visible.
        $theme2 = $theme;
        $theme2['pages']['home']['blocks'] = [$heroBlock, $block];
        $bindings = app(ThemeDataBindingRegistry::class)->resolveBlockLists([$block]);
        $html = app(ThemeCanvasRenderer::class)->setContext(['theme_bindings' => $bindings])->renderBlocks([$heroBlock, $block]);
        self::assertStringContainsString('پرفروش‌های هفته', $html);
        self::assertStringContainsString('میوه تازه', $html);
        self::assertStringContainsString('Fixture Sale Apple', $html);
    }
}

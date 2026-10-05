<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\ThemeCanvas\DataBinding\ThemeDataBindingRegistry;
use App\Services\ThemeCanvas\ThemeCanvasRenderer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use SaaSPlatform\ThemeRuntime\BlockRenderer;
use Tests\Support\CreatesFoodMartBindingFixtures;
use Tests\Concerns\SeedsValidLicenseWithShop;
use Tests\TestCase;

class FoodMartDataBindingTest extends TestCase
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

    public function test_product_and_category_bindings_resolve_real_published_records_and_images(): void
    {
        $fixtures = $this->createFoodMartBindingFixtures();
        $blocks = [
            $this->block('products', 'foodmart_trending_products', 'products', ['sort' => 'most_viewed', 'limit' => 4]),
            $this->block('categories', 'foodmart_categories', 'product_categories', ['sort' => 'catalog_order', 'limit' => 3]),
        ];

        $bindings = app(ThemeDataBindingRegistry::class)->resolveBlockLists($blocks);

        self::assertSame(['Fixture Plain Bread', 'Fixture Sale Apple', 'Fixture Sold Out Juice', 'Fixture Product With An Intentionally Long Name For Responsive Card Verification'], array_column($bindings['products']['items'], 'title'));
        self::assertNotContains('Fixture Hidden Draft', array_column($bindings['products']['items'], 'title'));
        self::assertSame(3, $bindings['categories']['items'][0]['product_count']);
        self::assertStringContainsString('/uploads/foodmart-fixtures/secondary.png', $bindings['products']['items'][1]['image_url']);
        self::assertStringContainsString('/uploads/foodmart-fixtures/secondary.png', $bindings['categories']['items'][0]['image_url']);
        Storage::disk('uploads')->assertExists('foodmart-fixtures/fruit.png');

        $renderer = app(ThemeCanvasRenderer::class)->setContext(['theme_bindings' => $bindings]);
        $html = $renderer->renderBlocks($blocks);
        self::assertStringContainsString('product-grid row', $html);
        self::assertStringContainsString('category-carousel swiper', $html);
        self::assertStringContainsString('Fixture Plain Bread', $html);
        self::assertStringContainsString('Fixture Empty', $html);
        self::assertStringContainsString(route('shop.cart.add'), $html);
        self::assertStringContainsString('name="product_id" value="'.$fixtures['sale']->id.'"', $html);
        self::assertStringContainsString('Choose options', $html);
        self::assertStringNotContainsString('Sunstar Fresh Melon Juice', $html);
        self::assertStringNotContainsString('Fixture Hidden Draft', $html);
    }

    public function test_manual_filters_limits_sales_stock_and_empty_policies_are_deterministic(): void
    {
        $fixtures = $this->createFoodMartBindingFixtures();
        $manual = $this->block('manual', 'foodmart_product_carousel_best_selling', 'products', [
            'sort' => 'manual', 'selected_ids' => [$fixtures['out']->id, $fixtures['sale']->id, $fixtures['draft']->id], 'limit' => 2,
        ], 'empty_state');
        $bindings = app(ThemeDataBindingRegistry::class)->resolveBlockLists([$manual]);
        self::assertSame(['Fixture Sold Out Juice', 'Fixture Sale Apple'], array_column($bindings['manual']['items'], 'title'));

        $html = app(ThemeCanvasRenderer::class)->setContext(['theme_bindings' => $bindings])->renderBlock($manual);
        self::assertStringContainsString('products-carousel swiper', $html);
        self::assertStringContainsString('Fixture Sold Out Juice', $html);
        self::assertMatchesRegularExpression('/Fixture Sold Out Juice.*?<button[^>]+disabled/s', $html);
        self::assertStringContainsString('badge bg-success', $html);

        $orderId = DB::table('orders')->insertGetId([
            'number' => 'FIXTURE-COMPLETED', 'customer_name' => 'Fixture', 'customer_mobile' => '09120000000',
            'subtotal' => 180000, 'total' => 180000, 'status' => 'completed', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('order_items')->insert([
            'order_id' => $orderId, 'product_id' => $fixtures['sale']->id, 'product_title' => $fixtures['sale']->title,
            'price' => 90000, 'quantity' => 2, 'total' => 180000, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $selling = $this->block('selling', 'foodmart_product_carousel_best_selling', 'products', ['sort' => 'best_selling_30d', 'limit' => 4]);
        $sellingBinding = app(ThemeDataBindingRegistry::class)->resolveBlockLists([$selling]);
        self::assertSame(['Fixture Sale Apple'], array_column($sellingBinding['selling']['items'], 'title'));

        $empty = $this->block('empty', 'foodmart_trending_products', 'products', ['sort' => 'manual', 'selected_ids' => [999999], 'limit' => 2], 'empty_state');
        $emptyBinding = app(ThemeDataBindingRegistry::class)->resolveBlockLists([$empty]);
        self::assertStringContainsString('No products available.', app(ThemeCanvasRenderer::class)->setContext(['theme_bindings' => $emptyBinding])->renderBlock($empty));
        $empty['binding']['empty'] = 'hide';
        $hiddenBinding = app(ThemeDataBindingRegistry::class)->resolveBlockLists([$empty]);
        self::assertSame('', app(ThemeCanvasRenderer::class)->setContext(['theme_bindings' => $hiddenBinding])->renderBlock($empty));
    }

    public function test_contract_fails_closed_for_unknown_sources_queries_and_cross_tenant_connection_attempts(): void
    {
        $this->createFoodMartBindingFixtures();
        config(['database.connections.foreign_fixture' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]);
        Schema::connection('foreign_fixture')->create('products', function (Blueprint $table): void {
            $table->id(); $table->string('title'); $table->string('status');
        });
        DB::connection('foreign_fixture')->table('products')->insert(['title' => 'Wrong Tenant Product', 'status' => Product::STATUS_PUBLISHED]);

        $attempts = [
            $this->block('unknown', 'foodmart_trending_products', 'arbitrary_model', ['sort' => 'latest', 'limit' => 4]),
            ['id' => 'connection', 'type' => 'foodmart_trending_products', 'props' => [], 'binding' => ['source' => 'products', 'connection' => 'foreign_fixture', 'query' => ['sort' => 'latest', 'limit' => 4]]],
            $this->block('sort', 'foodmart_trending_products', 'products', ['sort' => 'raw_sql', 'limit' => 4]),
            $this->block('wrong_source', 'foodmart_categories', 'products', ['sort' => 'latest', 'limit' => 4]),
        ];
        $bindings = app(ThemeDataBindingRegistry::class)->resolveBlockLists($attempts);
        foreach ($bindings as $binding) {
            self::assertSame('invalid', $binding['status']);
            self::assertSame([], $binding['items']);
        }
        $html = app(ThemeCanvasRenderer::class)->setContext(['theme_bindings' => $bindings])->renderBlocks($attempts);
        self::assertStringNotContainsString('Wrong Tenant Product', $html);
        self::assertSame('', trim($html));
    }

    public function test_bound_media_paths_and_text_are_safe(): void
    {
        $fixtures = $this->createFoodMartBindingFixtures();
        $unsafe = Product::create([
            'title' => '\"><script>alert(1)</script>', 'slug' => 'fixture-unsafe-product',
            'category_id' => $fixtures['fruit']->id, 'price' => 100, 'stock' => 1,
            'status' => Product::STATUS_PUBLISHED, 'image' => '../../.env',
        ]);
        $block = $this->block('safe-media', 'foodmart_trending_products', 'products', ['sort' => 'manual', 'selected_ids' => [$unsafe->id], 'limit' => 1], 'empty_state');
        $bindings = app(ThemeDataBindingRegistry::class)->resolveBlockLists([$block]);

        self::assertNull($bindings['safe-media']['items'][0]['image_url']);
        $html = app(ThemeCanvasRenderer::class)->setContext(['theme_bindings' => $bindings])->renderBlock($block);
        self::assertStringNotContainsString('../../.env', $html);
        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    public function test_unbound_static_foodmart_output_remains_byte_identical(): void
    {
        $block = ['id' => 'same', 'type' => 'foodmart_trending_products', 'props' => []];
        $before = (new BlockRenderer())->renderBlock($block);
        $after = app(ThemeCanvasRenderer::class)->setContext(['theme_bindings' => []])->renderBlock($block);
        self::assertSame($before, $after);
    }

    public function test_binding_query_count_stays_bounded_as_item_count_grows(): void
    {
        $this->createFoodMartBindingFixtures();
        $blocks = [
            $this->block('budget-products', 'foodmart_trending_products', 'products', ['sort' => 'catalog_order', 'limit' => 24]),
            $this->block('budget-categories', 'foodmart_categories', 'product_categories', ['sort' => 'catalog_order', 'limit' => 24]),
        ];
        DB::flushQueryLog();
        DB::enableQueryLog();
        app(ThemeDataBindingRegistry::class)->resolveBlockLists($blocks);
        $queries = DB::getQueryLog();
        $count = count($queries);
        DB::disableQueryLog();

        self::assertLessThanOrEqual(12, $count, "Binding resolution exceeded the bounded query budget ({$count}): ".json_encode(array_column($queries, 'query')));
    }

    public function test_bound_product_form_uses_the_existing_cart_workflow(): void
    {
        $fixtures = $this->createFoodMartBindingFixtures();
        $block = $this->block('cart', 'foodmart_trending_products', 'products', ['sort' => 'manual', 'selected_ids' => [$fixtures['sale']->id], 'limit' => 1]);
        $bindings = app(ThemeDataBindingRegistry::class)->resolveBlockLists([$block]);
        $html = app(ThemeCanvasRenderer::class)->setContext(['theme_bindings' => $bindings])->renderBlock($block);
        self::assertStringContainsString('action="'.route('shop.cart.add').'"', $html);

        $this->from('/')->post(route('shop.cart.add'), ['product_id' => $fixtures['sale']->id, 'quantity' => 1])->assertRedirect('/');
        $this->assertSame($fixtures['sale']->id, session('cart')[$fixtures['sale']->id.':0']['product_id']);
        $this->assertSame(1, session('cart')[$fixtures['sale']->id.':0']['quantity']);
    }

    public function test_public_canvas_and_incremental_editor_render_the_same_bound_records(): void
    {
        $fixtures = $this->createFoodMartBindingFixtures();
        $block = $this->block('parity', 'foodmart_trending_products', 'products', ['sort' => 'manual', 'selected_ids' => [$fixtures['sale']->id], 'limit' => 1]);
        $theme = [
            'name' => 'FoodMart binding fixture', 'header_blocks' => [], 'footer_blocks' => [],
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
            $response->assertSee('Fixture Sale Apple');
            $response->assertSee('/uploads/foodmart-fixtures/secondary.png', false);
            $response->assertDontSee('Fixture Hidden Draft');
            $response->assertDontSee('Sunstar Fresh Melon Juice');
        }
        self::assertStringContainsString('Fixture Sale Apple', $incremental->json('html'));
        self::assertStringContainsString('data-tb-block-id="parity"', $incremental->json('html'));
    }

    /** @param array<string,mixed> $query @return array<string,mixed> */
    private function block(string $id, string $type, string $source, array $query, string $empty = 'hide'): array
    {
        return ['id' => $id, 'type' => $type, 'props' => [], 'binding' => ['source' => $source, 'query' => $query, 'empty' => $empty]];
    }
}

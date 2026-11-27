<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PlatformApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SaaSPlatform\CoreUtils\Icons\IconRegistry;
use SaaSPlatform\ThemeRuntime\Access\AssetRef;
use SaaSPlatform\ThemeRuntime\BlockRegistry;
use Tests\TestCase;

class CanvasAssetIntegrationPhase1Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        @touch(storage_path('installed.lock'));
        config(['site.owner_site' => true]);
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    protected function createAdmin(): User
    {
        $user = User::factory()->create(['is_admin' => true]);
        return $user;
    }

    public function test_palette_icon_is_registered_in_core_utils(): void
    {
        $svg = IconRegistry::get('palette');
        $this->assertNotNull($svg, 'Palette icon must be defined in CoreUtils IconRegistry');
        $this->assertStringContainsString('<circle', $svg);
    }

    public function test_button_technical_properties_are_hidden_in_block_registry(): void
    {
        $schema = BlockRegistry::all()['button']['schema'];

        $this->assertTrue($schema['pack_slug']['hidden'] ?? false, 'pack_slug must be hidden');
        $this->assertTrue($schema['preset_slug']['hidden'] ?? false, 'preset_slug must be hidden');
        $this->assertTrue($schema['asset_version']['hidden'] ?? false, 'asset_version must be hidden');
        $this->assertTrue($schema['preset_snapshot']['hidden'] ?? false, 'preset_snapshot must be hidden');
    }

    public function test_canvas_templates_merges_remote_granted_headers_footers_and_sections(): void
    {
        $user = $this->createAdmin();

        // Mock PlatformApiService returning marketplace assets
        $mockPlatform = $this->mock(PlatformApiService::class);
        $mockPlatform->shouldReceive('canvasMarketplaceAssets')->andReturn([
            'headers' => [
                [
                    'kind' => 'header',
                    'slug' => 'test-granted-header',
                    'title' => 'هدر تست اعطایی',
                    'description' => 'توضیحات هدر',
                    'category_label' => 'ناوبری',
                    'preview_image' => 'https://example.com/hdr.png',
                    'blocks' => [['type' => 'nav_menu', 'props' => []]],
                    'access_type' => 'free',
                    'price' => 0,
                    'is_granted' => true,
                ],
            ],
            'footers' => [
                [
                    'kind' => 'footer',
                    'slug' => 'test-granted-footer',
                    'title' => 'فوتر تست اعطایی',
                    'description' => 'توضیحات فوتر',
                    'category_label' => 'فوتر',
                    'preview_image' => 'https://example.com/ftr.png',
                    'blocks' => [['type' => 'text', 'props' => ['content' => 'کپی رایت']]],
                    'access_type' => 'free',
                    'price' => 0,
                    'is_granted' => true,
                ],
            ],
            'sections' => [
                [
                    'kind' => 'section',
                    'slug' => 'test-granted-section',
                    'title' => 'سکشن تست اعطایی',
                    'description' => 'توضیحات سکشن',
                    'category_label' => 'هیرو',
                    'preview_image' => 'https://example.com/sec.png',
                    'blocks' => [['type' => 'hero', 'props' => []]],
                    'access_type' => 'free',
                    'price' => 0,
                    'is_granted' => true,
                ],
            ],
            'buttons' => [],
            'icons' => [],
            'motions' => [],
        ]);

        $response = $this->actingAs($user)->getJson(route('admin.theme-design.canvas.templates'));
        $response->assertOk();

        $templates = collect($response->json('templates'));
        $ids = $templates->pluck('id')->all();

        $this->assertContains('remote:header:test-granted-header', $ids);
        $this->assertContains('remote:footer:test-granted-footer', $ids);
        $this->assertContains('remote:section:test-granted-section', $ids);
    }

    public function test_canvas_insert_template_inserts_remote_header_footer_and_section(): void
    {
        $user = $this->createAdmin();

        $mockPlatform = $this->mock(PlatformApiService::class);
        $mockPlatform->shouldReceive('canvasMarketplaceAssets')->andReturn([
            'headers' => [
                [
                    'slug' => 'remote-hdr-1',
                    'title' => 'هدر ۱',
                    'blocks' => [['type' => 'nav_menu', 'props' => ['title' => 'منو']]],
                ],
            ],
            'footers' => [
                [
                    'slug' => 'remote-ftr-1',
                    'title' => 'فوتر ۱',
                    'blocks' => [['type' => 'text', 'props' => ['content' => 'متن فوتر']]],
                ],
            ],
            'sections' => [
                [
                    'slug' => 'remote-sec-1',
                    'title' => 'سکشن ۱',
                    'blocks' => [['type' => 'hero', 'props' => ['heading' => 'عنوان هیرو']]],
                ],
            ],
        ]);

        // 1. Insert Remote Header
        $hdrRes = $this->actingAs($user)->postJson(route('admin.theme-design.canvas.insert-template'), [
            'template_id' => 'remote:header:remote-hdr-1',
        ]);
        $hdrRes->assertOk();
        $this->assertSame('header', $hdrRes->json('zone'));
        $this->assertNotEmpty($hdrRes->json('blocks'));
        $this->assertSame('nav_menu', $hdrRes->json('blocks.0.type'));

        // 2. Insert Remote Footer
        $ftrRes = $this->actingAs($user)->postJson(route('admin.theme-design.canvas.insert-template'), [
            'template_id' => 'remote:footer:remote-ftr-1',
        ]);
        $ftrRes->assertOk();
        $this->assertSame('footer', $ftrRes->json('zone'));
        $this->assertNotEmpty($ftrRes->json('blocks'));
        $this->assertSame('text', $ftrRes->json('blocks.0.type'));

        // 3. Insert Remote Section
        $secRes = $this->actingAs($user)->postJson(route('admin.theme-design.canvas.insert-template'), [
            'template_id' => 'remote:section:remote-sec-1',
        ]);
        $secRes->assertOk();
        $this->assertSame('content', $secRes->json('zone'));
        $this->assertNotEmpty($secRes->json('blocks'));
        $this->assertSame('hero', $secRes->json('blocks.0.type'));
    }

    public function test_standard_commercial_pages_are_defined_and_present_in_canvas(): void
    {
        $expectedPages = ['shop', 'product_detail', 'cart', 'checkout', 'blog', 'post_detail'];
        $constPages = \App\Http\Controllers\Admin\ThemeDesignController::STANDARD_COMMERCIAL_PAGES;

        foreach ($expectedPages as $key) {
            $this->assertArrayHasKey($key, $constPages, "Standard page {$key} must be defined in STANDARD_COMMERCIAL_PAGES");
            $this->assertNotEmpty($constPages[$key]['title']);
            $this->assertSame($key, $constPages[$key]['slug']);
        }
    }

    public function test_shop_and_blog_fallback_to_standard_views_when_canvas_has_no_blocks(): void
    {
        \App\Models\Setting::set('active_canvas_theme_data', json_encode([
            'global_styles' => [],
            'header_blocks' => [],
            'footer_blocks' => [],
            'pages' => [
                'shop' => ['title' => 'فروشگاه', 'slug' => 'shop', 'blocks' => []],
                'blog' => ['title' => 'وبلاگ', 'slug' => 'blog', 'blocks' => []],
            ],
        ]));

        $shopResponse = $this->get('/shop');
        // Shop should render standard shop view (200 OK)
        $shopResponse->assertOk();
        $shopResponse->assertViewIs('themes.default.shop.index');

        $blogResponse = $this->get('/blog');
        // Blog should render standard blog view (200 OK)
        $blogResponse->assertOk();
        $blogResponse->assertViewIs('themes.default.blog.index');
    }
}

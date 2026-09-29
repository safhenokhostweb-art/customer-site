<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\License;
use App\Models\Setting;
use App\Models\User;
use App\Services\ThemeCanvas\ThemeAssetLocalizationService;
use App\Services\ThemeCanvas\ThemeCanvasRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use SaaSPlatform\ThemeRuntime\BlockRenderer as SharedBlockRenderer;
use SaaSPlatform\ThemeRuntime\FoodMartBlockRendererTrait;
use Tests\TestCase;

class CanvasRuntimeParityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        config(['site.owner_site' => true]);
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    public function test_foodmart_canvas_uses_the_public_document_runtime_and_customer_local_assets(): void
    {
        $theme = $this->foodMartTheme();
        Setting::set('active_canvas_theme_data', json_encode($theme), 'theme');
        Setting::set('canvas_preview_token', 'runtime-parity-token', 'theme');

        $this->mock(ThemeAssetLocalizationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('runtimeAssets')->times(3)->andReturn([
                'css' => ['/storage/theme-assets/foodmart/10/hash/bootstrap.css', '/storage/theme-assets/foodmart/10/hash/style.css'],
                'js' => ['/storage/theme-assets/foodmart/10/hash/bootstrap.js'],
                'files' => ['images/logo.png' => '/storage/theme-assets/foodmart/10/hash/images/logo.png'],
            ]);
        });

        $public = $this->get('/')->assertOk();
        $canvas = $this->actingAs($this->admin())
            ->get(route('admin.theme-design.canvas-live-preview', ['page' => 'home']))
            ->assertOk();
        $fullPreview = $this->get(route('canvas.preview', ['token' => 'runtime-parity-token', 'page' => 'home']))
            ->assertOk();

        foreach ([$public, $canvas, $fullPreview] as $response) {
            $response->assertSee('<html lang="en" dir="ltr">', false);
            $response->assertSee('/storage/theme-assets/foodmart/10/hash/bootstrap.css', false);
            $response->assertSee('/storage/theme-assets/foodmart/10/hash/style.css', false);
            $response->assertSee('/storage/theme-assets/foodmart/10/hash/bootstrap.js', false);
            $response->assertSee('/storage/theme-assets/foodmart/10/hash/images/logo.png', false);
            $response->assertSee('id="offcanvasCart"', false);
            $response->assertSee('Fresh Smoothie & Summer Juice', false);
            $response->assertSee('FoodMart', false);
            $response->assertDontSee('class="canvas-site-header tb-site-header"', false);
            self::assertDoesNotMatchRegularExpression('/build\/assets\/app-[^"\']+\.css/', $response->getContent());
        }

        $canvas->assertSee('data-tb-block-type="foodmart_offcanvas_cart"', false);
        $canvas->assertSee('data-tb-block-type="foodmart_hero"', false);
        $canvas->assertSee('data-tb-block-id="fm-cart"', false);
        $canvas->assertDontSee('class="tb-canvas-block', false);
        $canvas->assertDontSee('id="builder-edit-bar"', false);
        $canvas->assertSee('window.location.origin', false);
        $canvas->assertDontSee('fonts.googleapis.com', false);
        $fullPreview->assertSee('window.ThemeCanvasRuntime = Object.freeze', false);
        $fullPreview->assertSee('root.CanvasIdentity = contract', false);
        $fullPreview->assertDontSee('window.CanvasEditor', false);
        $fullPreview->assertDontSee('cdn.tailwindcss.com', false);
        $fullPreview->assertDontSee('builder-edit-bar', false);
    }

    public function test_canvas_keeps_unknown_block_fallback_and_legacy_platform_defaults(): void
    {
        $theme = [
            'name' => 'Legacy',
            'header_blocks' => [],
            'footer_blocks' => [],
            'pages' => ['home' => [
                'title' => 'Legacy home',
                'slug' => 'home',
                'is_home' => true,
                'blocks' => [
                    ['id' => 'known', 'type' => 'heading', 'props' => ['text' => 'Legacy heading']],
                    ['id' => 'unknown', 'type' => 'future_unknown_block', 'props' => []],
                ],
            ]],
            'global_styles' => [],
        ];
        Setting::set('active_canvas_theme_data', json_encode($theme), 'theme');

        $response = $this->actingAs($this->admin())
            ->get(route('admin.theme-design.canvas-live-preview', ['page' => 'home']))
            ->assertOk();

        $response->assertSee('<html lang="fa" dir="rtl">', false);
        $response->assertSee('Legacy heading');
        $response->assertSee('data-tb-block-type="future_unknown_block"', false);
        $response->assertSee('tb-placeholder-invalid', false);
        $response->assertSee('class="canvas-site-header"', false);
    }

    public function test_foodmart_renderer_has_one_shared_dom_authority_and_generic_canvas_has_no_slug_branch(): void
    {
        self::assertTrue(is_subclass_of(ThemeCanvasRenderer::class, SharedBlockRenderer::class));
        self::assertContains(FoodMartBlockRendererTrait::class, class_uses_recursive(SharedBlockRenderer::class));

        $adapter = file_get_contents(base_path('../saas-platform/app/Services/ThemeBuilder/FoodMart/FoodMartBlockRenderer.php'));
        self::assertDoesNotMatchRegularExpression('/function\s+renderFoodmart/i', $adapter);

        foreach ([
            app_path('Services/ThemeCanvas/ThemeRuntimeDocument.php'),
            app_path('Http/Controllers/Admin/ThemeDesignController.php'),
            resource_path('views/admin/theme-design/canvas-live-preview.blade.php'),
        ] as $genericRuntimeFile) {
            self::assertStringNotContainsString('foodmart', strtolower(file_get_contents($genericRuntimeFile)));
        }
    }

    private function foodMartTheme(): array
    {
        return [
            'name' => 'FoodMart',
            'slug' => 'foodmart',
            'header_blocks' => [['id' => 'fm-header', 'type' => 'foodmart_header', 'props' => []]],
            'footer_blocks' => [['id' => 'fm-footer', 'type' => 'foodmart_footer', 'props' => []]],
            'pages' => ['home' => [
                'title' => 'FoodMart Home',
                'slug' => 'home',
                'is_home' => true,
                'blocks' => [
                    ['id' => 'fm-cart', 'type' => 'foodmart_offcanvas_cart', 'props' => []],
                    ['id' => 'fm-hero', 'type' => 'foodmart_hero', 'props' => []],
                ],
            ]],
            'global_styles' => [
                'direction' => 'ltr',
                'chrome_owner' => 'theme',
                'presentation_css_owner' => 'theme',
            ],
            'theme_assets' => ['localized' => true, 'assets' => []],
        ];
    }

    private function admin(): User
    {
        License::create([
            'license_key' => 'CANVAS-PARITY-'.uniqid(),
            'domain' => request()->getHost(),
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => ['shop', 'blog'],
        ]);

        return User::factory()->create(['is_admin' => true]);
    }
}

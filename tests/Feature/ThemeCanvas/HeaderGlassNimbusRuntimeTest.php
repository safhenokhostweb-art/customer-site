<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\User;
use App\Services\AssetProvisioning\AssetEntitlementClient;
use App\Services\PlatformApiService;
use App\Services\ThemeBuilder\BlockRenderer;
use App\Services\ThemeCanvas\BlockRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use SaaSPlatform\ThemeRuntime\Access\AssetAccessDecision;
use SaaSPlatform\ThemeRuntime\Access\AssetAccessGate;
use SaaSPlatform\ThemeRuntime\Access\AssetRef;
use SaaSPlatform\ThemeRuntime\Access\SiteAssetEntitlements;
use Tests\TestCase;

class HeaderGlassNimbusRuntimeTest extends TestCase
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

    public function test_customer_site_block_renderer_renders_header_glass_nimbus_with_rtl_and_popover(): void
    {
        $renderer = new BlockRenderer();
        $renderer->setContext(['current_url' => '/blog']);

        $block = [
            'type' => 'header_glass_nimbus',
            'id' => 'cust_nimbus_1',
            'props' => [
                'brand_name' => 'فروشگاه تستی',
                'logo_badge_symbol' => '◆',
                'home_url' => '/',
                'menu_items' => [
                    ['label' => 'صفحه نخست', 'url' => '/'],
                    ['label' => 'وبلاگ تخصصی', 'url' => '/blog'],
                    ['label' => 'محصولات', 'url' => '/shop'],
                ],
                'secondary_label' => 'ورود / ثبت‌نام',
                'secondary_url' => '/login',
                'cta_label' => 'شروع سریع',
                'cta_url' => '/register',
                'cta_color_start' => '#e20c35',
                'cta_color_end' => '#c4008d',
                'ink_color' => '#211c48',
                'asset_version' => '1.0.0',
            ],
        ];

        $html = $renderer->renderBlock($block);

        $this->assertStringContainsString('tb-header-nimbus', $html);
        $this->assertStringContainsString('فروشگاه تستی', $html);
        $this->assertStringContainsString('◆', $html);
        $this->assertStringContainsString('href="/blog" class="tb-nav-header-link is-active" aria-current="page"', $html);
        $this->assertStringContainsString('←', $html);
        $this->assertStringContainsString('شروع سریع', $html);
        $this->assertStringContainsString('tb-nav-header-menu-panel', $html);
        $this->assertStringContainsString('tb-nav-header-toggle', $html);
        $this->assertStringContainsString('--color-tgn01-cta1:#e20c35', $html);
        $this->assertStringContainsString('--color-tgn01-cta2:#c4008d', $html);
        $this->assertStringContainsString('--color-tgn01-ink:#211c48', $html);
    }

    public function test_palette_hides_header_glass_nimbus_without_grant_and_shows_with_grant(): void
    {
        $all = BlockRegistry::all();
        $byCategory = [];
        foreach ($all as $type => $def) {
            $cat = $def['category'] ?? 'utility';
            $byCategory[$cat][$type] = $def;
        }

        // 1. Without grant: must be filtered out / hidden from palette
        $noGrantEntitlements = SiteAssetEntitlements::none();
        [$filteredNoGrant] = AssetAccessGate::filterPalette($byCategory, $noGrantEntitlements);
        $this->assertArrayNotHasKey('header_glass_nimbus', $filteredNoGrant['utility'] ?? []);

        // 2. With grant header:header-glass-nimbus: must be unlocked and present in palette
        $grantedEntitlements = SiteAssetEntitlements::make(null, [], ['header:header-glass-nimbus']);
        [$filteredGranted] = AssetAccessGate::filterPalette($byCategory, $grantedEntitlements);
        $this->assertArrayHasKey('header_glass_nimbus', $filteredGranted['utility'] ?? []);
    }

    public function test_canvas_templates_hides_paid_nimbus_header_without_grant_and_insert_rejects_with_403(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $catalog = [
            'headers' => [
                [
                    'kind' => 'header',
                    'slug' => 'header-glass-nimbus',
                    'title' => 'هدر شناور شیشه‌ای مدرن',
                    'access_type' => 'paid',
                    'price' => 490000,
                    'current_version' => '1.0.0',
                    'is_granted' => false,
                    'blocks' => [
                        ['id' => 'hdr_1', 'type' => 'header_glass_nimbus', 'props' => []],
                    ],
                ],
            ],
            'footers' => [],
            'sections' => [],
            'buttons' => [],
            'icons' => [],
        ];

        $mockPlatform = Mockery::mock(PlatformApiService::class);
        $mockPlatform->shouldReceive('canvasMarketplaceAssets')->andReturn($catalog);
        $this->app->instance(PlatformApiService::class, $mockPlatform);

        // 1. Site with no grants
        $mockEntitlements = Mockery::mock(AssetEntitlementClient::class);
        $mockEntitlements->shouldReceive('current')->andReturn(SiteAssetEntitlements::none());
        $this->app->instance(AssetEntitlementClient::class, $mockEntitlements);

        // In template picker, ungranted paid header must NOT be listed
        $resp = $this->actingAs($user)->getJson(route('admin.theme-design.canvas.templates', ['type' => 'header']));
        $resp->assertOk();
        $templates = $resp->json('templates');
        $ids = array_column($templates, 'id');
        $this->assertNotContains('remote:header:header-glass-nimbus', $ids);

        // Inserting ungranted paid header must be refused with 403
        $insertResp = $this->actingAs($user)->postJson(route('admin.theme-design.canvas.insert-template'), [
            'template_id' => 'remote:header:header-glass-nimbus',
        ]);
        $insertResp->assertStatus(403);
        $insertResp->assertJson(['success' => false]);

        // 2. Site with active grant for header:header-glass-nimbus
        $grantedEntitlements = SiteAssetEntitlements::make(null, [], ['header:header-glass-nimbus']);
        $mockEntitlementsGranted = Mockery::mock(AssetEntitlementClient::class);
        $mockEntitlementsGranted->shouldReceive('current')->andReturn($grantedEntitlements);
        $this->app->instance(AssetEntitlementClient::class, $mockEntitlementsGranted);

        // In template picker, granted header MUST be listed
        $respGranted = $this->actingAs($user)->getJson(route('admin.theme-design.canvas.templates', ['type' => 'header']));
        $respGranted->assertOk();
        $templatesGranted = $respGranted->json('templates');
        $grantedIds = array_column($templatesGranted, 'id');
        $this->assertContains('remote:header:header-glass-nimbus', $grantedIds);

        // Inserting granted header succeeds
        $insertGrantedResp = $this->actingAs($user)->postJson(route('admin.theme-design.canvas.insert-template'), [
            'template_id' => 'remote:header:header-glass-nimbus',
        ]);
        $insertGrantedResp->assertOk();
        $insertGrantedResp->assertJson(['success' => true]);
        $this->assertSame('header_glass_nimbus', $insertGrantedResp->json('blocks.0.type'));
    }

    public function test_nav_header_glass_nimbus_renders_pure_css_styles_and_all_unified_features(): void
    {
        $renderer = new BlockRenderer();
        $renderer->setContext(['current_url' => '/shop']);

        $block = [
            'type' => 'nav_header',
            'id' => 'cust_glass_test_1',
            'props' => [
                'header_skin' => 'glass_nimbus',
                'logo_text' => 'شیشه نوین',
                'logo_badge_symbol' => '◆',
                'logo_url' => '/',
                'topbar_enable' => true,
                'topbar_phone' => '09120000000',
                'nav_source' => 'custom_links',
                'nav_links' => [
                    ['label' => 'خانه', 'url' => '/', 'icon' => 'home'],
                    ['label' => 'فروشگاه', 'url' => '/shop', 'icon' => 'shopping-bag'],
                ],
                'show_search' => true,
                'show_cart' => true,
                'show_account' => true,
                'show_dark_toggle' => true,
                'show_social_in_main_header' => true,
                'social_links' => [
                    ['platform' => 'telegram', 'url' => 'https://t.me/example'],
                    ['platform' => 'eitaa', 'url' => 'https://eitaa.com/example'],
                ],
                'cta_text' => 'ثبت سفارش',
                'cta_url' => '/order',
                'cta_style' => 'gradient',
                'cta_color_start' => '#e20c35',
                'cta_color_end' => '#c4008d',
                'ink_color' => '#211c48',
                'backdrop_blur' => 20,
                'glass_bg_opacity' => 60,
                'glass_floating_top' => 15,
                'asset_version' => '1.3.0',
            ],
        ];

        $html = $renderer->renderBlock($block);

        $this->assertStringContainsString('tb-header-skin--glass-nimbus', $html);
        $this->assertStringContainsString('tb-header-cta-primary', $html);
        $this->assertStringContainsString('←', $html);
        $this->assertStringContainsString('--color-tgn01-cta1:#e20c35', $html);
        $this->assertStringContainsString('--color-tgn01-cta2:#c4008d', $html);
        $this->assertStringContainsString('--color-tgn01-ink:#211c48', $html);
        $this->assertStringContainsString('--tgn01-glass-blur:20px', $html);
        $this->assertStringContainsString('--tgn01-glass-top:15px', $html);
        $this->assertStringContainsString('09120000000', $html);
        $this->assertStringContainsString('tb-nav-header-search', $html);
        $this->assertStringContainsString('tb-nav-header-cart', $html);
        $this->assertStringContainsString('tb-nav-header-account', $html);
        $this->assertStringContainsString('tb-theme-toggle', $html);
        $this->assertStringContainsString('telegram', $html);
        $this->assertStringContainsString('eitaa', $html);

        $css = file_get_contents(\SaaSPlatform\ThemeRuntime\Providers\ThemeRuntimeServiceProvider::cssHeaderPath());
        $this->assertStringContainsString('.tb-header-skin--glass-nimbus', $css);
        $this->assertStringContainsString('backdrop-filter: blur(var(--tgn01-glass-blur, 16px)) saturate(180%)', $css);
        $this->assertStringContainsString('position: sticky', $css);
        $this->assertStringContainsString('border-radius: 1rem', $css);
    }
}

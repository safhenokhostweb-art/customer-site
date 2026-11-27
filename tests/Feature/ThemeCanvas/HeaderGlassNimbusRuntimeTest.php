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

        $this->assertStringContainsString('class="tb-header-nimbus', $html);
        $this->assertStringContainsString('فروشگاه تستی', $html);
        $this->assertStringContainsString('◆', $html);
        $this->assertStringContainsString('href="/blog" aria-current="page"', $html);
        $this->assertStringContainsString('←', $html);
        $this->assertStringContainsString('شروع سریع', $html);
        $this->assertStringContainsString('class="tb-header-nimbus-mobile', $html);
        $this->assertStringContainsString('popover', $html);
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
}

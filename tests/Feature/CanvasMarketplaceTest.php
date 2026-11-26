<?php

namespace Tests\Feature;

use App\Models\License;
use App\Models\User;
use App\Services\AssetProvisioning\AssetEntitlementClient;
use App\Services\PlatformApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SaaSPlatform\ThemeRuntime\Access\AssetRef;
use SaaSPlatform\ThemeRuntime\Access\SiteAssetEntitlements;
use Tests\TestCase;

class CanvasMarketplaceTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Concerns\SeedsValidLicenseWithShop;

    private static int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedValidLicense();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    private function makeAdmin(): User
    {
        self::$seq++;
        return User::factory()->create([
            'mobile' => '0912' . str_pad((string) self::$seq, 7, '0', STR_PAD_LEFT),
            'is_admin' => true,
        ]);
    }

    public function test_marketplace_index_renders_with_catalog_and_tabs(): void
    {
        $admin = $this->makeAdmin();

        $platform = $this->mock(PlatformApiService::class);
        $platform->shouldReceive('canvasMarketplaceAssets')->once()->andReturn([
            'buttons' => [
                [
                    'kind' => AssetRef::KIND_BUTTON_PACK,
                    'slug' => 'neon-pack',
                    'title' => 'پکیج دکمه نئونی',
                    'access_type' => 'paid',
                    'price' => 50000,
                    'current_version' => '1.0.0',
                    'is_granted' => false,
                    'presets' => [],
                ]
            ],
            'icons' => [
                [
                    'kind' => AssetRef::KIND_ICON_PACK,
                    'slug' => 'free-nature-icons',
                    'title' => 'آیکن‌های طبیعت',
                    'access_type' => 'free',
                    'price' => 0,
                    'current_version' => '1.0.0',
                    'is_granted' => false,
                    'sample_items' => [],
                ]
            ],
            'headers' => [],
            'motions' => [],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.canvas-marketplace.index'));
        $response->assertOk();
        $response->assertViewIs('admin.canvas-marketplace.index');
        $response->assertSee('مارکت‌پلیس دارایی‌های Canvas');
        $response->assertSee('پکیج دکمه نئونی');
    }

    public function test_tenant_can_claim_free_asset_and_refreshes_entitlements(): void
    {
        $admin = $this->makeAdmin();

        $platform = $this->mock(PlatformApiService::class);
        $platform->shouldReceive('canvasMarketplaceClaim')
            ->with(AssetRef::KIND_ICON_PACK, 'free-nature-icons')
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'دارایی با موفقیت برای سایت شما فعال شد.',
            ]);

        $response = $this->actingAs($admin)->post(route('admin.canvas-marketplace.claim'), [
            'asset_kind' => AssetRef::KIND_ICON_PACK,
            'asset_key' => 'free-nature-icons',
        ]);

        $response->assertRedirect(route('admin.canvas-marketplace.index', ['tab' => 'icons']));
        $response->assertSessionHas('success');
    }

    public function test_tenant_can_purchase_paid_asset_and_refreshes_entitlements(): void
    {
        $admin = $this->makeAdmin();

        $platform = $this->mock(PlatformApiService::class);
        $platform->shouldReceive('canvasMarketplacePurchase')
            ->with(AssetRef::KIND_BUTTON_PACK, 'neon-pack', \Mockery::any())
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'خرید با موفقیت انجام شد و دارایی فعال گردید.',
            ]);

        $response = $this->actingAs($admin)->post(route('admin.canvas-marketplace.purchase'), [
            'asset_kind' => AssetRef::KIND_BUTTON_PACK,
            'asset_key' => 'neon-pack',
        ]);

        $response->assertRedirect(route('admin.canvas-marketplace.index', ['tab' => 'buttons']));
        $response->assertSessionHas('success');
    }

    public function test_purchase_redirects_to_bank_gateway_when_redirect_url_provided(): void
    {
        $admin = $this->makeAdmin();

        $platform = $this->mock(PlatformApiService::class);
        $platform->shouldReceive('canvasMarketplacePurchase')
            ->with(AssetRef::KIND_BUTTON_PACK, 'neon-pack', \Mockery::any())
            ->once()
            ->andReturn([
                'success' => true,
                'redirect_url' => 'https://sep.shaparak.ir/OnlinePayment',
            ]);

        $response = $this->actingAs($admin)->post(route('admin.canvas-marketplace.purchase'), [
            'asset_kind' => AssetRef::KIND_BUTTON_PACK,
            'asset_key' => 'neon-pack',
        ]);

        $response->assertRedirect('https://sep.shaparak.ir/OnlinePayment');
    }

    public function test_index_handles_payment_success_and_failed_query_params(): void
    {
        $admin = $this->makeAdmin();

        $platform = $this->mock(PlatformApiService::class);
        $platform->shouldReceive('canvasMarketplaceAssets')->twice()->andReturn([
            'buttons' => [], 'icons' => [], 'headers' => [], 'motions' => []
        ]);

        // Success return
        $resSuccess = $this->actingAs($admin)->get(route('admin.canvas-marketplace.index', ['payment' => 'success', 'ref' => 'REF12345']));
        $resSuccess->assertOk();
        $resSuccess->assertSee('پرداخت با موفقیت انجام شد');
        $resSuccess->assertSee('REF12345');

        // Failed return
        $resFailed = $this->actingAs($admin)->get(route('admin.canvas-marketplace.index', ['payment' => 'failed', 'msg' => 'پرداخت لغو گردید']));
        $resFailed->assertOk();
        $resFailed->assertSee('پرداخت لغو گردید');
    }

    public function test_failed_claim_returns_error_message(): void
    {
        $admin = $this->makeAdmin();

        $platform = $this->mock(PlatformApiService::class);
        $platform->shouldReceive('canvasMarketplaceClaim')
            ->with(AssetRef::KIND_BUTTON_PACK, 'neon-pack')
            ->once()
            ->andReturn([
                'success' => false,
                'message' => 'این دارایی رایگان نیست و نیاز به خرید دارد.',
            ]);

        $response = $this->actingAs($admin)->post(route('admin.canvas-marketplace.claim'), [
            'asset_kind' => AssetRef::KIND_BUTTON_PACK,
            'asset_key' => 'neon-pack',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_canvas_editor_renders_safely_even_if_platform_throws_exception(): void
    {
        $admin = $this->makeAdmin();

        $platform = $this->mock(PlatformApiService::class);
        $platform->shouldReceive('canvasMarketplaceAssets')
            ->andThrow(new \RuntimeException('Connection timed out to panel.safhenokhost.ir'));

        $response = $this->actingAs($admin)->get(route('admin.theme-design.canvas', ['page' => 'home']));

        // Must never be 500! Failsafe renders cleanly
        $response->assertOk();
        $response->assertViewIs('admin.theme-design.canvas');
    }
}

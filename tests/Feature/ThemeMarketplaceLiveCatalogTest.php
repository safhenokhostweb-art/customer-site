<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ThemeMarketplaceController;
use App\Services\PlatformApiService;
use Illuminate\Http\Request;
use Tests\TestCase;

class ThemeMarketplaceLiveCatalogTest extends TestCase
{
    public function test_catalog_and_detail_are_refetched_after_platform_assignment_changes(): void
    {
        $platform = $this->mock(PlatformApiService::class);
        $platform->shouldReceive('isConfigured')->times(4)->andReturnTrue();
        $platform->shouldReceive('marketplaceThemes')->twice()->andReturn(
            [['id' => 1, 'name' => 'Theme A']],
            [['id' => 1, 'name' => 'Theme A'], ['id' => 2, 'name' => 'Theme B']],
        );
        $platform->shouldReceive('marketplaceTheme')->with(2)->twice()->andReturn(
            ['id' => 2, 'name' => 'Theme B', 'has_license' => false],
            ['id' => 2, 'name' => 'Theme B', 'has_license' => true],
        );

        $controller = app(ThemeMarketplaceController::class);
        $firstCatalog = $controller->index(Request::create('/admin/theme-marketplace'));
        $secondCatalog = $controller->index(Request::create('/admin/theme-marketplace'));
        $firstDetail = $controller->show(Request::create('/admin/theme-marketplace/2'), 2);
        $secondDetail = $controller->show(Request::create('/admin/theme-marketplace/2'), 2);

        $this->assertCount(1, $firstCatalog->getData()['themes']);
        $this->assertCount(2, $secondCatalog->getData()['themes']);
        $this->assertFalse($firstDetail->getData()['theme']['has_license']);
        $this->assertTrue($secondDetail->getData()['theme']['has_license']);
    }
}

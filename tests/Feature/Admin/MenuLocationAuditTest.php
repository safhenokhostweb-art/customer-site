<?php

namespace Tests\Feature\Admin;

use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsValidLicenseWithShop;
use Tests\TestCase;

/**
 * TASK 3/3 Phase 6.5C (BLD-27) — Menu location runtime-connection audit.
 * Menu models 5 locations (primary/footer/mobile/topbar/secondary), but
 * only primary and footer are wired to any renderer or render context —
 * mobile/topbar/secondary were a real, silent dead end: a customer could
 * create a "Mobile Menu" and it would never affect their site. Permanent
 * regression guard for the fix: only primary/footer are creatable going
 * forward.
 */
class MenuLocationAuditTest extends TestCase
{
    use RefreshDatabase, SeedsValidLicenseWithShop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markSiteAsInstalled();
    }

    private function admin(): User
    {
        $this->seedValidLicense(['menu']);
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_selectable_locations_only_include_runtime_connected_ones(): void
    {
        self::assertSame(['primary', 'footer'], array_keys(Menu::selectableLocations()));
    }

    public function test_creating_a_menu_at_a_dead_location_is_rejected(): void
    {
        foreach (['mobile', 'topbar', 'secondary'] as $deadLocation) {
            $response = $this->actingAs($this->admin())->post(route('admin.menus.store'), [
                'name' => 'تست', 'location' => $deadLocation,
            ]);

            $response->assertSessionHasErrors('location');
            self::assertSame(0, Menu::where('location', $deadLocation)->count(), "\"{$deadLocation}\" must not be creatable — it has no runtime-connected renderer");
        }
    }

    public function test_primary_and_footer_remain_creatable(): void
    {
        foreach (['primary', 'footer'] as $liveLocation) {
            $response = $this->actingAs($this->admin())->post(route('admin.menus.store'), [
                'name' => 'تست ' . $liveLocation, 'location' => $liveLocation,
            ]);

            $response->assertRedirect();
            self::assertSame(1, Menu::where('location', $liveLocation)->count());
        }
    }
}

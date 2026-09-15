<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsValidLicenseWithShop;
use Tests\TestCase;

/**
 * LIC-ANALYTICS-01 regression coverage.
 *
 * Prior to this fix, `routes/web.php` registered TWO routes named
 * `admin.analytics.index`: an unguarded one (first, so it always won URL
 * matching) pointing straight at `AnalyticsDashboardController`, and a
 * `module:analytics`-gated one further down pointing at the generic
 * `PlatformModuleController@show` stub. Because Laravel matches incoming
 * requests against the FIRST registered route for a URI, any authenticated
 * admin could reach the real analytics dashboard regardless of whether the
 * `analytics` add-on module was licensed for the site — the gate was dead
 * code. The fix removes the unguarded duplicate and moves the real
 * controller behind the single remaining `module:analytics` gate.
 */
class AnalyticsLicenseGateTest extends TestCase
{
    use RefreshDatabase, SeedsValidLicenseWithShop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markSiteAsInstalled();
    }

    public function test_exactly_one_route_is_registered_for_analytics_index(): void
    {
        $matches = collect(app('router')->getRoutes())
            ->filter(fn ($route) => $route->getName() === 'admin.analytics.index');

        $this->assertCount(1, $matches, 'admin.analytics.index must resolve to exactly one route authority.');
        $this->assertSame(
            \App\Http\Controllers\Admin\AnalyticsDashboardController::class,
            explode('@', $matches->first()->getActionName())[0],
        );
    }

    public function test_analytics_dashboard_is_denied_without_the_analytics_module_license(): void
    {
        // A valid license for an unrelated module ('shop') proves this isn't
        // simply "no license at all" — the analytics module specifically
        // must be absent from the license's module list.
        $this->seedValidLicense(['shop']);
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('admin.analytics.index'));

        $response->assertStatus(403);
    }

    public function test_analytics_dashboard_succeeds_once_the_analytics_module_is_licensed(): void
    {
        $this->seedValidLicense(['analytics']);
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('admin.analytics.index'));

        $response->assertOk();
        $response->assertViewIs('admin.analytics.dashboard');
        $response->assertViewHasAll(['kpis', 'revenueChart', 'usersChart', 'pvChart', 'topPages', 'deviceSplit', 'topProducts', 'tickets']);
    }
}

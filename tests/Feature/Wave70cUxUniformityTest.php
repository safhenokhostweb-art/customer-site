<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsValidLicenseWithShop;
use Tests\TestCase;

/**
 * Phase 2 / Wave 7.0c — UX Uniformity Sweep regression suite.
 *
 * Smoke tests for the four new admin guide partials, the reusable
 * breadcrumb partial, the five pages that opt-in to breadcrumbs, and
 * the placeholder-module "Coming Soon" badges. The tests are
 * deliberately narrow: they assert on Persian copy and DOM landmarks
 * the partials emit, so any unrelated refactor of admin/layout.blade.php
 * or sibling pages will not regress this suite.
 *
 * License seeding via `SeedsValidLicenseWithShop` mirrors Wave 7.0b
 * (blog + shop + forms entitlements satisfied for admin reachability).
 */
class Wave70cUxUniformityTest extends TestCase
{
    use RefreshDatabase, SeedsValidLicenseWithShop;

    /** @var User */
    private $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markSiteAsInstalled();
        // Module set covers every page under test: dashboard, forms,
        // orders/products/coupons (shop), posts (blog), plus `analytics`
        // as a representative placeholder/platform module for the
        // "Coming Soon" banner assertion.
        $this->seedValidLicense(['blog', 'shop', 'forms', 'analytics']);
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_admin_dashboard_renders_breadcrumb_with_dashboard_label(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $response->assertOk();
        $response->assertSee('admin-breadcrumb', false);
        $response->assertSee('aria-label="مسیر صفحه"', false);
        $response->assertSee('aria-current="page"', false);
        $response->assertSee('داشبورد', false);
    }

    public function test_admin_forms_index_renders_breadcrumb_with_two_segments(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.forms.index'));
        $response->assertOk();
        $response->assertSee('admin-breadcrumb', false);
        $response->assertSee('داشبورد', false);
        $response->assertSee('فرم‌ساز', false);
        $response->assertSee('admin-breadcrumb-link', false);
        $response->assertSee('admin-breadcrumb-current', false);
    }

    public function test_admin_orders_index_renders_breadcrumb_and_guide(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.orders.index'));
        $response->assertOk();
        $response->assertSee('admin-breadcrumb', false);
        $response->assertSee('سفارشات', false);
        $response->assertSee('راهنمای ماژول سفارش‌ها', false);
        $response->assertSee('۱۴۰۵/۰۲/۲۳', false);
    }

    public function test_admin_products_index_renders_breadcrumb_and_guide(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.products.index'));
        $response->assertOk();
        $response->assertSee('admin-breadcrumb', false);
        $response->assertSee('محصولات', false);
        $response->assertSee('راهنمای ماژول محصولات', false);
        $response->assertSee('۱۴۰۵/۰۲/۲۳', false);
    }

    public function test_admin_posts_index_renders_breadcrumb_and_guide(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.posts.index'));
        $response->assertOk();
        $response->assertSee('admin-breadcrumb', false);
        $response->assertSee('مطالب وبلاگ', false);
        $response->assertSee('راهنمای ماژول مطالب وبلاگ', false);
        $response->assertSee('۱۴۰۵/۰۲/۲۳', false);
    }

    public function test_admin_coupons_index_renders_guide_without_breadcrumb(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.coupons.index'));
        $response->assertOk();
        $response->assertSee('راهنمای ماژول کوپن‌ها', false);
        $response->assertSee('۱۴۰۵/۰۲/۲۳', false);
        // Coupons is explicitly OUT of the breadcrumb scope per the
        // approved Wave 7.0c analysis. Make that contract explicit.
        $response->assertDontSee('aria-label="مسیر صفحه"', false);
    }

    public function test_placeholder_platform_module_page_shows_coming_soon_banner(): void
    {
        // `analytics.index` resolves to PlatformModuleController::show with
        // a route-default `slug=analytics`. The slug is listed in
        // PlatformModuleController::MODULE_GUIDES, so the page renders the
        // placeholder template and must now carry the Coming Soon banner.
        $response = $this->actingAs($this->admin)->get(route('admin.analytics.index'));
        $response->assertOk();
        $response->assertSee('admin-coming-soon-banner', false);
        $response->assertSee('admin-coming-soon-badge', false);
        $response->assertSee('به‌زودی', false);
        $response->assertSee('در دست توسعه', false);
    }

    public function test_breadcrumb_partial_renders_nothing_when_crumbs_empty(): void
    {
        // Sanity: an opt-in partial must be a no-op when not configured.
        $rendered = view('admin.partials.breadcrumb', ['crumbs' => []])->render();
        $this->assertSame('', trim($rendered));
    }

    public function test_breadcrumb_partial_marks_last_item_as_current(): void
    {
        $rendered = view('admin.partials.breadcrumb', [
            'crumbs' => [
                ['label' => 'الف', 'url' => '/x'],
                ['label' => 'ب', 'url' => '/y'],
                ['label' => 'ج', 'url' => null],
            ],
        ])->render();

        // Non-terminal entries link out; the last one is plain text.
        $this->assertStringContainsString('href="/x"', $rendered);
        $this->assertStringContainsString('href="/y"', $rendered);
        $this->assertStringContainsString('aria-current="page"', $rendered);
        $this->assertStringContainsString('admin-breadcrumb-current', $rendered);
        $this->assertStringContainsString('›', $rendered);
    }
}

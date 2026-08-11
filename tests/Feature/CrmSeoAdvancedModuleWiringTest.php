<?php

namespace Tests\Feature;

use App\Helpers\AdminMenu;
use App\Models\License;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase X — regression tests for wiring the real crm-suite (Module id=58)
 * and seo-advanced (Module id=57) packages into customer-site.
 *
 * Covers the two slug-mismatch bugs fixed in AdminMenu::getStaticModuleSlugMap()
 * (the 'crm'/'seo_advanced' AdminMenu keys previously checked entitlement
 * against the wrong marketplace slugs — the dead placeholder Module id=46
 * for 'crm', and an underscore variant that never matched the real
 * hyphenated 'seo-advanced' slug) and the manifest-driven label/route
 * resolution added to replace AdminMenu's forms/tickets-only hardcoding.
 */
class CrmSeoAdvancedModuleWiringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    private function licenseWithModules(array $modules): License
    {
        return License::create([
            'license_key' => 'TEST-CRM1-SEO2-XXXX',
            'domain' => request()->getHost(),
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => $modules,
        ]);
    }

    // =====================================================================
    // Entitlement gating — the actual bug: AdminMenu checked the wrong slug
    // =====================================================================

    public function test_crm_menu_item_hidden_without_crm_suite_entitlement(): void
    {
        $this->licenseWithModules(['forms']);
        $this->assertFalse(AdminMenu::isVisible('crm'));
    }

    public function test_crm_menu_item_visible_with_real_crm_suite_slug(): void
    {
        $this->licenseWithModules(['crm-suite']);
        $this->assertTrue(AdminMenu::isVisible('crm'));
    }

    public function test_crm_menu_item_stays_hidden_for_dead_placeholder_slug(): void
    {
        // Module id=46's slug is plain 'crm' — the old, buggy check. Even if
        // a license somehow carries this dead placeholder slug, the fixed
        // moduleMap must NOT treat it as crm-suite entitlement.
        $this->licenseWithModules(['crm']);
        $this->assertFalse(AdminMenu::isVisible('crm'));
    }

    public function test_seo_advanced_menu_item_hidden_without_entitlement(): void
    {
        $this->licenseWithModules(['forms']);
        $this->assertFalse(AdminMenu::isVisible('seo_advanced'));
    }

    public function test_seo_advanced_menu_item_visible_with_real_hyphenated_slug(): void
    {
        $this->licenseWithModules(['seo-advanced']);
        $this->assertTrue(AdminMenu::isVisible('seo_advanced'));
    }

    public function test_seo_advanced_menu_item_stays_hidden_for_underscore_variant(): void
    {
        // The old bug: moduleMap checked 'seo_advanced' (underscore), which
        // never matches the real 'seo-advanced' (hyphen) license slug.
        $this->licenseWithModules(['seo_advanced']);
        $this->assertFalse(AdminMenu::isVisible('seo_advanced'));
    }

    // =====================================================================
    // Route resolution — was dead/legacy before this fix
    // =====================================================================

    public function test_crm_menu_route_points_at_real_package_not_dead_route(): void
    {
        $this->assertSame('crm.site.dashboard', AdminMenu::getRoute('crm'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('crm.site.dashboard'));
    }

    public function test_seo_advanced_menu_route_points_at_real_package_not_legacy_controller(): void
    {
        $this->assertSame('seo-advanced.site.report', AdminMenu::getRoute('seo_advanced'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('seo-advanced.site.report'));
    }

    public function test_platform_admin_crud_routes_are_not_loaded_into_customer_site(): void
    {
        // routes/web.php (crm-suite's and seo-advanced's platform-staff CRUD
        // surface) trusts a raw {siteId} route param — must never be
        // reachable from customer-site (would be a cross-tenant IDOR).
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('crm.admin.dashboard'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('seo-advanced.admin.dashboard'));
    }

    // =====================================================================
    // Manifest-driven label/route resolution (replaces forms/tickets-only hardcoding)
    // =====================================================================

    public function test_titles_resolve_from_the_modules_own_manifest(): void
    {
        $this->assertSame('مخاطبین و CRM', AdminMenu::getTitle('crm'));
        $this->assertSame('سئو پیشرفته', AdminMenu::getTitle('seo_advanced'));
    }

    public function test_all_manifest_menu_items_includes_crm_and_seo_advanced_without_hardcoding(): void
    {
        $items = AdminMenu::getAllManifestMenuItems();

        $this->assertArrayHasKey('crm', $items);
        $this->assertSame('crm-suite', $items['crm']['manifest_slug']);

        $this->assertArrayHasKey('seo-advanced', $items);
        $this->assertSame('seo-advanced', $items['seo-advanced']['manifest_slug']);

        // forms/tickets must still resolve — the fallback default in
        // getInstalledManifestSlugs() must not have regressed.
        $this->assertArrayHasKey('forms', $items);
        $this->assertArrayHasKey('tickets', $items);
    }

    // =====================================================================
    // No duplicate rendering: a real slug reachable via a static AdminMenu
    // key must never also flow through the dynamic manifest-fallback path.
    // =====================================================================

    public function test_crm_suite_and_seo_advanced_do_not_appear_twice_in_dynamic_groups(): void
    {
        $this->licenseWithModules(['crm-suite', 'seo-advanced']);

        $ref = new \ReflectionClass(AdminMenu::class);
        $method = $ref->getMethod('getDynamicPlatformMenuKeys');
        $method->setAccessible(true);
        $dynamicKeys = $method->invoke(null);

        $this->assertNotContains('crm-suite', $dynamicKeys, 'crm-suite must only render via its static AdminMenu key, not also dynamically');
        $this->assertNotContains('seo-advanced', $dynamicKeys, 'seo-advanced must only render via its static AdminMenu key, not also dynamically');
    }
}

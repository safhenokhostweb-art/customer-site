<?php

namespace Tests\Unit\ThemeCanvas\Dependency;

use App\Models\License;
use App\Services\TenantContext;
use App\Services\ThemeCanvas\Dependency\ConfigExtensionEntitlementProvider;
use App\Services\ThemeCanvas\Dependency\ExtensionDependencyResolver;
use App\Services\ThemeCanvas\Dependency\InstalledExtensionProvider;
use App\Services\ThemeCanvas\Dependency\ModuleDependencyResolver;
use App\Services\ThemeCanvas\Dependency\ThemeDependencyAvailabilityResolver;
use App\Services\ThemeCanvas\Dependency\ThemeDependencyAvailabilityResult;
use App\Services\ThemeCanvas\Dependency\ThemeUpgradeDependencyGate;
use App\Services\ThemeCanvas\Extension\ExtensionBlockTypeRegistry;
use App\Services\ThemeCanvas\Extension\ExtensionProductIdentity;
use App\Services\ThemeCanvas\ThemeUpgradeReconciliationResult;
use App\Services\ThemeCanvas\ThemeUpgradeReconciler;
use Tests\TestCase;

/**
 * Phase 3E-E (corrected) — proves the Extension SDK plugs into the
 * EXISTING generic Theme dependency architecture through one adapter,
 * with dependency identity representing the extension PRODUCT (vendor +
 * package, never a bare vendor and never a persisted block name), and
 * with package presence and tenant entitlement resolved as two
 * genuinely independent, request-fresh facts.
 */
class ExtensionDependencyResolverTest extends TestCase
{
    private function licenseWithKey(string $key): License
    {
        return new License(['license_key' => $key, 'modules' => []]);
    }

    private function tenantWithLicense(string $key): TenantContext
    {
        $tenant = $this->app->make(TenantContext::class);
        $tenant->set($this->licenseWithKey($key));
        return $tenant;
    }

    private function tenantWithNoLicense(): TenantContext
    {
        $tenant = $this->app->make(TenantContext::class);
        $tenant->clear();
        return $tenant;
    }

    private function resolverFor(TenantContext $tenant): ExtensionDependencyResolver
    {
        return new ExtensionDependencyResolver(
            new InstalledExtensionProvider(),
            new ConfigExtensionEntitlementProvider($tenant)
        );
    }

    protected function tearDown(): void
    {
        ExtensionBlockTypeRegistry::reset();
        config(['extensions.installed' => [], 'extensions.entitlements' => []]);
        parent::tearDown();
    }

    // A. same vendor can own package-one and package-two independently
    public function test_a_same_vendor_can_own_two_independent_packages(): void
    {
        $one = ExtensionProductIdentity::fromParts('acme', 'gallery-pro');
        $two = ExtensionProductIdentity::fromParts('acme', 'forms-pro');

        $this->assertSame('acme/gallery-pro', $one->identifier);
        $this->assertSame('acme/forms-pro', $two->identifier);
        $this->assertNotSame($one->identifier, $two->identifier);
    }

    public function test_a_malformed_vendor_rejected(): void
    {
        $this->assertFalse(ExtensionProductIdentity::isValidIdentifier('Not Valid/gallery-pro'));
    }

    public function test_a_malformed_package_rejected(): void
    {
        $this->assertFalse(ExtensionProductIdentity::isValidIdentifier('acme/Not Valid!'));
    }

    public function test_a_individual_block_name_is_not_a_valid_package_identity(): void
    {
        // A block name alone (no slash) must never be mistaken for a
        // product identifier.
        $this->assertFalse(ExtensionProductIdentity::isValidIdentifier('hero_banner'));
    }

    // B. installed package-one does not satisfy package-two
    public function test_b_installed_package_one_does_not_satisfy_package_two(): void
    {
        config([
            'extensions.installed' => ['acme/gallery-pro' => ['version' => '1.0.0']],
            'extensions.entitlements' => ['lic-1' => ['acme/gallery-pro', 'acme/forms-pro']],
        ]);
        $resolver = $this->resolverFor($this->tenantWithLicense('lic-1'));

        $this->assertTrue($resolver->resolve('acme/gallery-pro')->present);
        $this->assertFalse($resolver->resolve('acme/forms-pro')->present);
    }

    // C. registered ext:acme:block does not satisfy acme/package-one dependency
    public function test_c_registered_block_type_does_not_satisfy_the_product_dependency(): void
    {
        ExtensionBlockTypeRegistry::registerExtensionType('acme', 'hero_banner', ['name' => 'Acme Hero']);
        config(['extensions.installed' => [], 'extensions.entitlements' => ['lic-1' => ['acme/gallery-pro']]]);
        $resolver = $this->resolverFor($this->tenantWithLicense('lic-1'));

        $this->assertTrue(ExtensionBlockTypeRegistry::has('ext:acme:hero_banner'), 'sanity: block IS registered');
        $this->assertFalse($resolver->resolve('acme/gallery-pro')->present, 'Block registration must never satisfy a product dependency.');
    }

    // D. globally installed package without tenant entitlement -> unavailable
    public function test_d_installed_without_entitlement_is_unavailable(): void
    {
        config(['extensions.installed' => ['acme/gallery-pro' => ['version' => '1.0.0']], 'extensions.entitlements' => []]);
        $resolver = $this->resolverFor($this->tenantWithLicense('lic-1'));

        $this->assertFalse($resolver->resolve('acme/gallery-pro')->present);
    }

    // E. entitled tenant without installed package -> unavailable
    public function test_e_entitled_without_installed_package_is_unavailable(): void
    {
        config(['extensions.installed' => [], 'extensions.entitlements' => ['lic-1' => ['acme/gallery-pro']]]);
        $resolver = $this->resolverFor($this->tenantWithLicense('lic-1'));

        $this->assertFalse($resolver->resolve('acme/gallery-pro')->present);
    }

    // F. installed + entitled + unconstrained -> available
    public function test_f_installed_and_entitled_unconstrained_is_available(): void
    {
        config(['extensions.installed' => ['acme/gallery-pro' => ['version' => '1.0.0']], 'extensions.entitlements' => ['lic-1' => ['acme/gallery-pro']]]);
        $this->app->instance(TenantContext::class, $this->tenantWithLicense('lic-1'));

        $result = $this->app->make(ThemeDependencyAvailabilityResolver::class)->check('extension', 'acme/gallery-pro', null);
        $this->assertSame(ThemeDependencyAvailabilityResult::AVAILABLE, $result->status);
    }

    // G. installed + entitled + compatible version -> available
    public function test_g_compatible_version_is_available(): void
    {
        config(['extensions.installed' => ['acme/gallery-pro' => ['version' => '1.5.0']], 'extensions.entitlements' => ['lic-1' => ['acme/gallery-pro']]]);
        $this->app->instance(TenantContext::class, $this->tenantWithLicense('lic-1'));

        $result = $this->app->make(ThemeDependencyAvailabilityResolver::class)->check('extension', 'acme/gallery-pro', '>=1.0.0');
        $this->assertSame(ThemeDependencyAvailabilityResult::AVAILABLE, $result->status);
    }

    // H. installed + entitled + incompatible version -> version incompatible
    public function test_h_incompatible_version_fails(): void
    {
        config(['extensions.installed' => ['acme/gallery-pro' => ['version' => '1.0.0']], 'extensions.entitlements' => ['lic-1' => ['acme/gallery-pro']]]);
        $this->app->instance(TenantContext::class, $this->tenantWithLicense('lic-1'));

        $result = $this->app->make(ThemeDependencyAvailabilityResolver::class)->check('extension', 'acme/gallery-pro', '>=2.0.0');
        $this->assertSame(ThemeDependencyAvailabilityResult::VERSION_INCOMPATIBLE, $result->status);
    }

    // I. installed + entitled + unknown version + constraint -> unverifiable
    public function test_i_unknown_version_with_constraint_is_unverifiable(): void
    {
        config(['extensions.installed' => ['acme/gallery-pro' => []], 'extensions.entitlements' => ['lic-1' => ['acme/gallery-pro']]]);
        $this->app->instance(TenantContext::class, $this->tenantWithLicense('lic-1'));

        $result = $this->app->make(ThemeDependencyAvailabilityResolver::class)->check('extension', 'acme/gallery-pro', '>=1.0.0');
        $this->assertSame(ThemeDependencyAvailabilityResult::UNVERIFIABLE, $result->status);
        $this->assertFalse($result->isAvailable());
    }

    // J. tenant A entitled, tenant B not entitled, same package -> A passes/B fails
    public function test_j_tenant_a_passes_tenant_b_fails_same_package(): void
    {
        config(['extensions.installed' => ['acme/gallery-pro' => ['version' => '1.0.0']], 'extensions.entitlements' => ['lic-a' => ['acme/gallery-pro']]]);

        $resolverA = $this->resolverFor($this->tenantWithLicense('lic-a'));
        $resolverB = $this->resolverFor($this->tenantWithLicense('lic-b'));

        $this->assertTrue($resolverA->resolve('acme/gallery-pro')->present);
        $this->assertFalse($resolverB->resolve('acme/gallery-pro')->present);
    }

    // K. repeated resolution on same resolver does not leak tenant A entitlement to B
    public function test_k_same_provider_instance_does_not_leak_entitlement_across_tenants(): void
    {
        config(['extensions.installed' => ['acme/gallery-pro' => ['version' => '1.0.0']], 'extensions.entitlements' => ['lic-a' => ['acme/gallery-pro']]]);

        $tenant = $this->app->make(TenantContext::class);
        $provider = new ConfigExtensionEntitlementProvider($tenant);
        $resolver = new ExtensionDependencyResolver(new InstalledExtensionProvider(), $provider);

        $tenant->set($this->licenseWithKey('lic-a'));
        $this->assertTrue($resolver->resolve('acme/gallery-pro')->present);

        $tenant->set($this->licenseWithKey('lic-b'));
        $this->assertFalse($resolver->resolve('acme/gallery-pro')->present, 'The SAME resolver/provider instance must not carry tenant A\'s entitlement over to tenant B.');
    }

    // L. generic hasTenant()/tenant existence alone does not grant extension access
    public function test_l_generic_tenant_existence_alone_does_not_grant_access(): void
    {
        config(['extensions.installed' => ['acme/gallery-pro' => ['version' => '1.0.0']], 'extensions.entitlements' => []]);
        $tenant = $this->tenantWithLicense('lic-real-but-not-entitled');

        $this->assertTrue($tenant->hasTenant(), 'sanity: a real, valid tenant/license exists');
        $this->assertFalse($this->resolverFor($tenant)->resolve('acme/gallery-pro')->present, 'A real tenant with no explicit entitlement must still be denied.');
    }

    public function test_l_no_license_at_all_is_denied(): void
    {
        config(['extensions.installed' => ['acme/gallery-pro' => ['version' => '1.0.0']], 'extensions.entitlements' => ['lic-1' => ['acme/gallery-pro']]]);
        $resolver = $this->resolverFor($this->tenantWithNoLicense());

        $this->assertFalse($resolver->resolve('acme/gallery-pro')->present);
    }

    // M. required unavailable extension still becomes a BLOCKING conflict
    // through the unchanged generic ThemeUpgradeDependencyGate
    public function test_m_upgrade_gate_blocks_on_unavailable_required_extension(): void
    {
        config(['extensions.installed' => [], 'extensions.entitlements' => []]);
        $availabilityResolver = new ThemeDependencyAvailabilityResolver([
            new ExtensionDependencyResolver(new InstalledExtensionProvider(), new ConfigExtensionEntitlementProvider($this->tenantWithLicense('lic-1'))),
        ]);

        $gated = (new ThemeUpgradeDependencyGate($availabilityResolver))->gate($this->emptyReconciliationResult(), [
            ['type' => 'extension', 'identifier' => 'acme/gallery-pro', 'version_constraint' => null, 'is_required' => true],
        ]);

        $this->assertTrue($gated->hasBlockingConflicts());
        $this->assertFalse($gated->canApply());
    }

    // N. compatible extension still passes that same generic gate
    public function test_n_upgrade_gate_passes_on_available_compatible_extension(): void
    {
        config(['extensions.installed' => ['acme/gallery-pro' => ['version' => '1.0.0']], 'extensions.entitlements' => ['lic-1' => ['acme/gallery-pro']]]);
        $availabilityResolver = new ThemeDependencyAvailabilityResolver([
            new ExtensionDependencyResolver(new InstalledExtensionProvider(), new ConfigExtensionEntitlementProvider($this->tenantWithLicense('lic-1'))),
        ]);

        $gated = (new ThemeUpgradeDependencyGate($availabilityResolver))->gate($this->emptyReconciliationResult(), [
            ['type' => 'extension', 'identifier' => 'acme/gallery-pro', 'version_constraint' => '>=1.0.0', 'is_required' => true],
        ]);

        $this->assertFalse($gated->hasBlockingConflicts());
        $this->assertTrue($gated->canApply());
    }

    // O. ModuleDependencyResolver remains unchanged/green
    public function test_o_module_dependency_resolver_is_unaffected(): void
    {
        $resolver = new ModuleDependencyResolver($this->tenantWithNoLicense());
        $this->assertTrue($resolver->supports('module'));
        $this->assertFalse($resolver->supports('extension'));
        $this->assertFalse($resolver->resolve('blog')->present);
    }

    // P. unknown ext:* payload survives reconciliation unchanged
    public function test_p_unknown_ext_block_is_preserved_by_reconciliation_independent_of_dependency_state(): void
    {
        config(['extensions.installed' => [], 'extensions.entitlements' => []]); // dependency NOT available

        $base = ['header_blocks' => [], 'footer_blocks' => [], 'pages' => [], 'global_styles' => [], 'settings' => []];
        $withBlock = [
            'header_blocks' => [],
            'footer_blocks' => [],
            'pages' => [[
                'slug' => 'home',
                'blocks' => [['id' => 'x1', 'type' => 'ext:acme:hero_banner', 'props' => ['title' => 'hi']]],
            ]],
            'global_styles' => [],
            'settings' => [],
        ];

        $result = (new ThemeUpgradeReconciler())->reconcile($base, $withBlock, $base);

        $ids = array_column($result->reconciledTheme['pages'][0]['blocks'] ?? [], 'id');
        $this->assertContains('x1', $ids);
    }

    public function test_reconciler_source_has_no_dependency_lookup(): void
    {
        $path = (new \ReflectionClass(ThemeUpgradeReconciler::class))->getFileName();
        $source = file_get_contents($path);

        $this->assertStringNotContainsString('Dependency', $source);
        $this->assertStringNotContainsString('ExtensionDependencyResolver', $source);
    }

    private function emptyReconciliationResult(): ThemeUpgradeReconciliationResult
    {
        return new ThemeUpgradeReconciliationResult(
            reconciledTheme: ['header_blocks' => [], 'footer_blocks' => [], 'pages' => [], 'global_styles' => [], 'settings' => []],
            conflicts: [],
            preservedCustomizations: [],
            remoteAdditions: [],
            detachedBlocks: [],
            structuralFallbacks: [],
            warnings: [],
            fingerprint: 'test-fingerprint',
        );
    }
}

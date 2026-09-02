<?php

namespace Tests\Unit\Builder\Extension;

use App\Builder\Definition\ThemeBuilderElementDefinition;
use App\Builder\Definition\ThemeBuilderExtensionBlock;
use App\Builder\Definition\ThemeBuilderExtensionDefinition;
use App\Builder\Definition\ThemeBuilderExtensionRegistrationException;
use App\Builder\ElementRegistry;
use App\Builder\Extension\ThemeBuilderExtensionLoader;
use App\Builder\Extension\ThemeBuilderExtensionProviderContract;
use App\Builder\Extension\ThemeBuilderExtensionRegistrar;
use App\Builder\Rendering\ElementRenderer;
use App\Models\License;
use App\Services\TenantContext;
use App\Services\ThemeCanvas\Dependency\ConfigExtensionEntitlementProvider;
use App\Services\ThemeCanvas\Dependency\ExtensionDependencyResolver;
use App\Services\ThemeCanvas\Dependency\InstalledExtensionProvider;
use App\Services\ThemeCanvas\Extension\ExtensionBlockTypeRegistry;
use App\Services\ThemeCanvas\Extension\ExtensionIdentityRegistrationException;
use App\Services\ThemeCanvas\Extension\ExtensionProductIdentity;
use App\Services\ThemeCanvas\ThemeUpgradeReconciler;
use Tests\TestCase;
use Tests\Unit\Builder\Extension\Fixtures\DemoCardElement;
use Tests\Unit\Builder\Extension\Fixtures\DemoContainerElement;

/**
 * Phase 3E-G — proves the first complete V1 Extension runtime registration
 * path: product identity → one/many ext:* block claims → declarative
 * Builder definitions → renderers participating in the EXISTING
 * ElementRenderer pipeline → optional assets, all coordinated by
 * ThemeBuilderExtensionRegistrar without a fourth mutable registry, and
 * fully atomic (validate every block first, mutate nothing on any
 * failure).
 */
class ThemeBuilderExtensionRegistrarTest extends TestCase
{
    protected function tearDown(): void
    {
        ExtensionBlockTypeRegistry::reset();
        ElementRegistry::clearCache();
        parent::tearDown();
    }

    private function cardBlock(string $vendor = 'acme'): ThemeBuilderExtensionBlock
    {
        return ThemeBuilderExtensionBlock::make(
            "ext:{$vendor}:demo_card",
            ThemeBuilderElementDefinition::fromElementClass(DemoCardElement::class),
            DemoCardElement::class,
        );
    }

    private function containerBlock(string $vendor = 'acme'): ThemeBuilderExtensionBlock
    {
        return ThemeBuilderExtensionBlock::make(
            "ext:{$vendor}:demo_container",
            ThemeBuilderElementDefinition::fromElementClass(DemoContainerElement::class),
            DemoContainerElement::class,
        );
    }

    private function product(string $vendor = 'acme', string $package = 'demo-suite'): ExtensionProductIdentity
    {
        return ExtensionProductIdentity::fromParts($vendor, $package);
    }

    // F. one product registers multiple ext:* blocks
    public function test_f_one_product_registers_multiple_blocks(): void
    {
        $package = ThemeBuilderExtensionDefinition::make($this->product(), '1.0.0', ThemeBuilderExtensionDefinition::SDK_CONTRACT_V1, [
            $this->cardBlock(),
            $this->containerBlock(),
        ]);

        ThemeBuilderExtensionRegistrar::register($package);

        $this->assertTrue(ElementRegistry::has('ext:acme:demo_card'));
        $this->assertTrue(ElementRegistry::has('ext:acme:demo_container'));
        $this->assertTrue(ExtensionBlockTypeRegistry::has('ext:acme:demo_card'));
        $this->assertTrue(ExtensionBlockTypeRegistry::has('ext:acme:demo_container'));
    }

    // B/C. product identity and version retained on the definition object
    public function test_bc_product_identity_and_version_are_retained(): void
    {
        $package = ThemeBuilderExtensionDefinition::make($this->product(), '2.3.1', ThemeBuilderExtensionDefinition::SDK_CONTRACT_V1, [$this->cardBlock()]);

        $this->assertSame('acme/demo-suite', $package->product->identifier);
        $this->assertSame('2.3.1', $package->packageVersion);
    }

    // D. supported SDK contract accepted
    public function test_d_supported_sdk_contract_is_accepted(): void
    {
        $package = ThemeBuilderExtensionDefinition::make($this->product(), '1.0.0', ThemeBuilderExtensionDefinition::SDK_CONTRACT_V1, [$this->cardBlock()]);
        $this->assertSame(1, $package->sdkContract);
    }

    // E. unsupported SDK contract rejected
    public function test_e_unsupported_sdk_contract_is_rejected(): void
    {
        $this->expectException(ThemeBuilderExtensionRegistrationException::class);
        ThemeBuilderExtensionDefinition::make($this->product(), '1.0.0', 99, [$this->cardBlock()]);
    }

    // G. package vendor/block vendor mismatch rejected
    public function test_g_block_vendor_mismatch_is_rejected(): void
    {
        $this->expectException(ThemeBuilderExtensionRegistrationException::class);
        ThemeBuilderExtensionDefinition::make($this->product('acme'), '1.0.0', ThemeBuilderExtensionDefinition::SDK_CONTRACT_V1, [
            $this->cardBlock('other-vendor'),
        ]);
    }

    // H. malformed block identity rejected
    public function test_h_malformed_block_identity_is_rejected(): void
    {
        $this->expectException(ExtensionIdentityRegistrationException::class);
        ThemeBuilderExtensionBlock::make(
            'ext:acme:Not Valid!',
            ThemeBuilderElementDefinition::fromElementClass(DemoCardElement::class),
            DemoCardElement::class,
        );
    }

    // I. duplicate block within same package rejected
    public function test_i_duplicate_block_within_same_package_is_rejected(): void
    {
        $this->expectException(ThemeBuilderExtensionRegistrationException::class);
        ThemeBuilderExtensionDefinition::make($this->product(), '1.0.0', ThemeBuilderExtensionDefinition::SDK_CONTRACT_V1, [
            $this->cardBlock(),
            $this->cardBlock(),
        ]);
    }

    // J. canonical collision rejected
    public function test_j_canonical_collision_is_rejected(): void
    {
        $block = ThemeBuilderExtensionBlock::make(
            'ext:acme:demo_card',
            ThemeBuilderElementDefinition::fromControls('ext:acme:demo_card', 'x', 'content', '', []),
            DemoCardElement::class,
        );
        // Simulate a canonical-colliding claim by directly probing the
        // registry's own preflight (a real collision needs a canonical
        // bare type, which can never be ext:-prefixed by construction —
        // this proves the underlying isClaimable() gate itself, reused
        // unmodified from 3E-B).
        $this->assertFalse(\App\Services\ThemeCanvas\Extension\ExtensionBlockTypeRegistry::isClaimable('heading', 'acme', []));
    }

    // K. host-only collision rejected
    public function test_k_host_only_collision_is_rejected(): void
    {
        $this->assertFalse(\App\Services\ThemeCanvas\Extension\ExtensionBlockTypeRegistry::isClaimable('spacer', 'acme', []));
    }

    // L. extension-extension collision rejected
    public function test_l_extension_vs_extension_collision_is_rejected(): void
    {
        $packageOne = ThemeBuilderExtensionDefinition::make($this->product('acme', 'suite-one'), '1.0.0', ThemeBuilderExtensionDefinition::SDK_CONTRACT_V1, [$this->cardBlock()]);
        ThemeBuilderExtensionRegistrar::register($packageOne);

        // A second, different vendor cannot claim the exact same
        // ext:acme:demo_card block type another package already owns.
        $conflicting = ThemeBuilderExtensionBlock::make(
            'ext:acme:demo_card',
            ThemeBuilderElementDefinition::fromControls('ext:acme:demo_card', 'Different', 'content', '', []),
            DemoContainerElement::class,
        );
        $packageTwo = ThemeBuilderExtensionDefinition::make($this->product('acme', 'suite-two'), '1.0.0', ThemeBuilderExtensionDefinition::SDK_CONTRACT_V1, [$conflicting]);

        $this->expectException(ThemeBuilderExtensionRegistrationException::class);
        ThemeBuilderExtensionRegistrar::register($packageTwo);
    }

    // M. duplicate product with different definition rejected
    public function test_m_same_product_different_definition_is_rejected(): void
    {
        $packageV1 = ThemeBuilderExtensionDefinition::make($this->product(), '1.0.0', ThemeBuilderExtensionDefinition::SDK_CONTRACT_V1, [$this->cardBlock()]);
        ThemeBuilderExtensionRegistrar::register($packageV1);

        $differentDefinition = ThemeBuilderExtensionBlock::make(
            'ext:acme:demo_card',
            ThemeBuilderElementDefinition::fromControls('ext:acme:demo_card', 'A totally different definition', 'content', '', []),
            DemoCardElement::class,
        );
        $packageV2 = ThemeBuilderExtensionDefinition::make($this->product(), '2.0.0', ThemeBuilderExtensionDefinition::SDK_CONTRACT_V1, [$differentDefinition]);

        $this->expectException(ThemeBuilderExtensionRegistrationException::class);
        ThemeBuilderExtensionRegistrar::register($packageV2);
    }

    public function test_same_product_identical_definition_is_idempotent(): void
    {
        $package = ThemeBuilderExtensionDefinition::make($this->product(), '1.0.0', ThemeBuilderExtensionDefinition::SDK_CONTRACT_V1, [$this->cardBlock()]);

        ThemeBuilderExtensionRegistrar::register($package);
        ThemeBuilderExtensionRegistrar::register($package); // repeat boot, same exact package

        $this->assertTrue(ElementRegistry::has('ext:acme:demo_card'));
        $this->assertSame(DemoCardElement::class, ElementRegistry::get('ext:acme:demo_card'));
    }

    // N. registration failure is atomic — zero partial blocks remain
    public function test_n_failed_package_registration_leaves_zero_partial_blocks(): void
    {
        $conflicting = ThemeBuilderExtensionBlock::make(
            'ext:acme:demo_card',
            ThemeBuilderElementDefinition::fromControls('ext:acme:demo_card', 'x', 'content', '', []),
            DemoContainerElement::class, // a DIFFERENT class than whatever might already own this type
        );

        // Pre-claim ext:acme:demo_card with a different definition so the
        // package below is guaranteed to fail on its SECOND block, proving
        // the FIRST block (demo_container, otherwise perfectly valid) never
        // gets committed either.
        ExtensionBlockTypeRegistry::registerExtensionType('acme', 'demo_card', ['name' => 'pre-existing, different']);

        $package = ThemeBuilderExtensionDefinition::make($this->product(), '1.0.0', ThemeBuilderExtensionDefinition::SDK_CONTRACT_V1, [
            $this->containerBlock(), // would succeed alone
            $conflicting,            // will fail — different definition already claims this identity
        ]);

        try {
            ThemeBuilderExtensionRegistrar::register($package);
            $this->fail('Expected package registration to fail.');
        } catch (ThemeBuilderExtensionRegistrationException) {
            $this->assertFalse(ElementRegistry::has('ext:acme:demo_container'), 'The FIRST, individually-valid block must NOT have been committed.');
        }
    }

    // O. successful definitions visible through ElementRegistry
    // P. ownership of registered ext:* definitions == extension
    public function test_op_registered_definitions_are_visible_and_classified_as_extension(): void
    {
        $package = ThemeBuilderExtensionDefinition::make($this->product(), '1.0.0', ThemeBuilderExtensionDefinition::SDK_CONTRACT_V1, [$this->cardBlock()]);
        ThemeBuilderExtensionRegistrar::register($package);

        $definition = ElementRegistry::definition('ext:acme:demo_card');
        $this->assertNotNull($definition);
        $this->assertSame(ThemeBuilderElementDefinition::OWNERSHIP_EXTENSION, $definition->ownership);
        $this->assertSame(ThemeBuilderElementDefinition::OWNERSHIP_EXTENSION, ElementRegistry::ownershipOf('ext:acme:demo_card'));
    }

    // Q. renderer dispatch works / R. prop validation/coercion still works
    public function test_qr_renderer_dispatch_and_prop_validation_work(): void
    {
        $package = ThemeBuilderExtensionDefinition::make($this->product(), '1.0.0', ThemeBuilderExtensionDefinition::SDK_CONTRACT_V1, [$this->cardBlock()]);
        ThemeBuilderExtensionRegistrar::register($package);

        $html = (new ElementRenderer())->render('ext:acme:demo_card', [
            'title' => 'Hello', 'content' => '<p>ok</p>',
        ], ['id' => 'x1']);

        $this->assertStringContainsString('Hello', $html);
        $this->assertStringContainsString('<p>ok</p>', $html);
    }

    public function test_prop_coercion_applies_defaults_for_missing_fields(): void
    {
        $package = ThemeBuilderExtensionDefinition::make($this->product(), '1.0.0', ThemeBuilderExtensionDefinition::SDK_CONTRACT_V1, [$this->cardBlock()]);
        ThemeBuilderExtensionRegistrar::register($package);

        $html = (new ElementRenderer())->render('ext:acme:demo_card', [], ['id' => 'x2']);
        $this->assertIsString($html);
        $this->assertStringNotContainsString('Element failed to render', $html);
    }

    // S. sanitizer boundary still holds (synthetic extension stored-XSS regression)
    public function test_s_extension_renderer_stored_xss_is_neutralized_through_canonical_sanitizer(): void
    {
        $package = ThemeBuilderExtensionDefinition::make($this->product(), '1.0.0', ThemeBuilderExtensionDefinition::SDK_CONTRACT_V1, [$this->cardBlock()]);
        ThemeBuilderExtensionRegistrar::register($package);

        $html = (new ElementRenderer())->render('ext:acme:demo_card', [
            'title' => 'x',
            'content' => '<a href="javascript:alert(1)" onmouseover="alert(2)">click</a><img src=x onerror=alert(3)>',
        ], ['id' => 'xss1']);

        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('onmouseover', $html);
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringNotContainsString('<img', $html);
    }

    // T. container metadata survives / U. allowed_children survives
    public function test_tu_container_metadata_survives_registration(): void
    {
        $package = ThemeBuilderExtensionDefinition::make($this->product(), '1.0.0', ThemeBuilderExtensionDefinition::SDK_CONTRACT_V1, [$this->containerBlock()]);
        ThemeBuilderExtensionRegistrar::register($package);

        $definition = ElementRegistry::definition('ext:acme:demo_container');
        $this->assertTrue($definition->isContainer);
        $this->assertNull($definition->allowedChildren);
    }

    // V. assets collected through existing contract / W. no unexpected duplication
    public function test_vw_assets_are_collected_and_not_duplicated(): void
    {
        $package = ThemeBuilderExtensionDefinition::make($this->product(), '1.0.0', ThemeBuilderExtensionDefinition::SDK_CONTRACT_V1, [$this->cardBlock()]);
        ThemeBuilderExtensionRegistrar::register($package);

        $renderer = new ElementRenderer();
        $renderer->render('ext:acme:demo_card', ['title' => 'a'], ['id' => 'a1']);
        $renderer->render('ext:acme:demo_card', ['title' => 'b'], ['id' => 'a2']); // same asset again

        $assets = $renderer->getCollectedAssets();
        $this->assertCount(1, $assets['css']);
        $this->assertSame('/vendor/acme/demo-card.css', $assets['css'][0]);
    }

    // X. repeated boot deterministic/idempotent
    public function test_x_loader_boot_is_idempotent_across_repeated_calls(): void
    {
        config(['theme_builder_extensions.providers' => [DemoSuiteProvider::class]]);

        ThemeBuilderExtensionLoader::boot($this->app);
        ThemeBuilderExtensionLoader::boot($this->app);

        $this->assertTrue(ElementRegistry::has('ext:acme:demo_card'));
        $this->assertTrue(ElementRegistry::has('ext:acme:demo_container'));
    }

    // Y. package registration does not grant tenant entitlement
    // Z. block registration does not satisfy dependency resolver
    // AA. entitlement without installed package still unavailable
    public function test_yz_aa_registration_never_implies_dependency_entitlement(): void
    {
        $package = ThemeBuilderExtensionDefinition::make($this->product(), '1.0.0', ThemeBuilderExtensionDefinition::SDK_CONTRACT_V1, [$this->cardBlock()]);
        ThemeBuilderExtensionRegistrar::register($package);

        config(['extensions.installed' => [], 'extensions.entitlements' => []]);
        $tenant = $this->app->make(TenantContext::class);
        $tenant->set(new License(['license_key' => 'lic-1', 'modules' => []]));
        $resolver = new ExtensionDependencyResolver(new InstalledExtensionProvider(), new ConfigExtensionEntitlementProvider($tenant));

        $this->assertFalse($resolver->resolve('acme/demo-suite')->present, 'Successful block registration must never itself satisfy the dependency resolver.');

        // AA: entitled but package not installed still unavailable
        config(['extensions.installed' => [], 'extensions.entitlements' => ['lic-1' => ['acme/demo-suite']]]);
        $this->assertFalse($resolver->resolve('acme/demo-suite')->present);
    }

    // AB. unknown ext:* persisted content remains preserved
    public function test_ab_unknown_extension_block_still_preserved_by_reconciliation(): void
    {
        $base = ['header_blocks' => [], 'footer_blocks' => [], 'pages' => [], 'global_styles' => [], 'settings' => []];
        $withBlock = [
            'header_blocks' => [],
            'footer_blocks' => [],
            'pages' => [['slug' => 'home', 'blocks' => [['id' => 'u1', 'type' => 'ext:acme:demo_card', 'props' => []]]]],
            'global_styles' => [],
            'settings' => [],
        ];

        $result = (new ThemeUpgradeReconciler())->reconcile($base, $withBlock, $base);
        $ids = array_column($result->reconciledTheme['pages'][0]['blocks'] ?? [], 'id');
        $this->assertContains('u1', $ids);
    }

    public function test_reconciler_source_still_has_no_registry_lookup(): void
    {
        $path = (new \ReflectionClass(ThemeUpgradeReconciler::class))->getFileName();
        $source = file_get_contents($path);
        $this->assertStringNotContainsString('ElementRegistry', $source);
        $this->assertStringNotContainsString('ExtensionBlockTypeRegistry', $source);
    }

    // Invalid provider / invalid renderer class fail closed
    public function test_invalid_provider_class_fails_closed(): void
    {
        config(['theme_builder_extensions.providers' => [\stdClass::class]]);
        $this->expectException(ThemeBuilderExtensionRegistrationException::class);
        ThemeBuilderExtensionLoader::boot($this->app);
    }

    public function test_renderer_class_not_implementing_element_interface_is_rejected(): void
    {
        $this->expectException(ThemeBuilderExtensionRegistrationException::class);
        ThemeBuilderExtensionBlock::make(
            'ext:acme:demo_card',
            ThemeBuilderElementDefinition::fromControls('ext:acme:demo_card', 'x', 'content', '', []),
            \stdClass::class,
        );
    }
}

/**
 * Test-only provider used by test_x above.
 */
final class DemoSuiteProvider implements ThemeBuilderExtensionProviderContract
{
    public function definition(): ThemeBuilderExtensionDefinition
    {
        return ThemeBuilderExtensionDefinition::make(
            ExtensionProductIdentity::fromParts('acme', 'demo-suite'),
            '1.0.0',
            ThemeBuilderExtensionDefinition::SDK_CONTRACT_V1,
            [
                ThemeBuilderExtensionBlock::make('ext:acme:demo_card', ThemeBuilderElementDefinition::fromElementClass(DemoCardElement::class), DemoCardElement::class),
                ThemeBuilderExtensionBlock::make('ext:acme:demo_container', ThemeBuilderElementDefinition::fromElementClass(DemoContainerElement::class), DemoContainerElement::class),
            ],
        );
    }
}

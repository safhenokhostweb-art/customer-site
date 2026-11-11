<?php

namespace Tests\Unit\Builder;

use App\Builder\Definition\ThemeBuilderElementDefinition;
use App\Builder\ElementRegistry;
use App\Services\ThemeCanvas\Dependency\ConfigExtensionEntitlementProvider;
use App\Services\ThemeCanvas\Dependency\ExtensionDependencyResolver;
use App\Services\ThemeCanvas\Dependency\InstalledExtensionProvider;
use App\Services\ThemeCanvas\Extension\ExtensionBlockTypeRegistry;
use App\Services\ThemeCanvas\Extension\ExtensionIdentityRegistrationException;
use App\Services\TenantContext;
use SaaSPlatform\ThemeRuntime\BlockRegistry as CanonicalBlockRegistry;
use Tests\TestCase;

/**
 * Phase 3E-F — proves the unified ownership boundary: every registered
 * Builder element type has ONE deterministic classification (canonical
 * adapter / host-only / extension), derived purely from the existing
 * authoritative sources (canonical BlockRegistry's own type map, the
 * ext: namespace convention) — no fourth mutable registry, no separately
 * maintained ownership flag.
 */
class RegistryOwnershipBoundaryTest extends TestCase
{
    private const CANONICAL_DELEGATING = ['heading', 'text', 'image', 'button', 'divider', 'spacer', 'section', 'grid', 'columns', 'hero', 'container'];
    private const HOST_ONLY = ['row', 'column'];

    protected function tearDown(): void
    {
        ExtensionBlockTypeRegistry::reset();
        parent::tearDown();
    }

    // A/B. all 13 elements classified correctly (11 canonical, 2 host-only)
    public function test_ab_all_10_canonical_delegating_elements_classify_as_canonical_adapter(): void
    {
        foreach (self::CANONICAL_DELEGATING as $type) {
            $this->assertSame(
                ThemeBuilderElementDefinition::OWNERSHIP_CANONICAL_ADAPTER,
                ElementRegistry::ownershipOf($type),
                "{$type} should classify as canonical_adapter"
            );
        }
    }

    // C. row/column classified host-only
    public function test_c_spacer_row_column_classify_as_host_only(): void
    {
        foreach (self::HOST_ONLY as $type) {
            $this->assertSame(
                ThemeBuilderElementDefinition::OWNERSHIP_HOST_ONLY,
                ElementRegistry::ownershipOf($type),
                "{$type} should classify as host_only"
            );
        }
    }

    // D. no existing persisted type renamed
    public function test_d_all_13_persisted_type_strings_are_unchanged(): void
    {
        $expected = array_merge(self::CANONICAL_DELEGATING, self::HOST_ONLY);
        foreach ($expected as $type) {
            $this->assertTrue(ElementRegistry::has($type), "{$type} must still be registered under its original persisted type string");
        }
        $this->assertCount(13, ElementRegistry::definitions());
        $this->assertArrayHasKey('module:motion', \App\Services\ThemeCanvas\BlockRegistry::all());
    }

    // Host-only types were deliberately NOT registered as canonical host
    // types (registerHostType()) — see class docblock / ADR for why: doing
    // so would leak into ThemeCanvas\BlockRegistry::all()'s existing merge
    // (self::$blocks + canonical::all()), silently adding spacer/row/column
    // as addable ThemeCanvas block types, which is observable
    // ThemeCanvas-editor behavior this phase must never change.
    public function test_host_only_types_were_not_registered_in_canonical_host_registry(): void
    {
        foreach (self::HOST_ONLY as $type) {
            $this->assertFalse(CanonicalBlockRegistry::isHostRegisteredType($type), "{$type} must not be registered as a canonical host type");
            $this->assertFalse(CanonicalBlockRegistry::isCanonicalType($type));
        }
    }

    public function test_theme_canvas_block_registry_does_not_gain_host_only_element_types(): void
    {
        $themeCanvasTypes = \App\Services\ThemeCanvas\BlockRegistry::all();
        foreach (self::HOST_ONLY as $type) {
            $this->assertArrayNotHasKey($type, $themeCanvasTypes, "ThemeCanvas\\BlockRegistry must not gain '{$type}' as a side effect of Element SDK classification");
        }
    }

    // Canonical adapters were never separately registered as host types —
    // proving the Element SDK class is a rendering/editor ADAPTER around
    // canonical, not a new runtime ownership claim (§9).
    public function test_canonical_adapters_have_no_separate_host_registration(): void
    {
        foreach (self::CANONICAL_DELEGATING as $type) {
            $this->assertFalse(CanonicalBlockRegistry::isHostRegisteredType($type), "{$type} must never be separately host-registered — it is a canonical adapter, not a new ownership claim");
        }
    }

    // E/F reuse the existing 3E-B fail-closed collision contract
    // (ExtensionIdentityCollisionContractTest) — not re-implemented here.
    // G/H. extension attempts on host-only / canonical bare names rejected
    public function test_gh_extension_cannot_claim_a_host_only_or_canonical_bare_name(): void
    {
        foreach (array_merge(self::HOST_ONLY, self::CANONICAL_DELEGATING) as $type) {
            try {
                ExtensionBlockTypeRegistry::assertNotReserved($type);
                $this->fail("Expected rejection registering bare type \"{$type}\" as an extension identity.");
            } catch (ExtensionIdentityRegistrationException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    // L. registration/classification order is deterministic (pure function, no mutable state)
    public function test_l_classification_is_stable_regardless_of_lookup_order(): void
    {
        $firstPass = [];
        foreach (array_merge(self::CANONICAL_DELEGATING, self::HOST_ONLY) as $type) {
            $firstPass[$type] = ElementRegistry::ownershipOf($type);
        }

        $secondPass = [];
        foreach (array_reverse(array_merge(self::CANONICAL_DELEGATING, self::HOST_ONLY)) as $type) {
            $secondPass[$type] = ElementRegistry::ownershipOf($type);
        }

        ksort($firstPass);
        ksort($secondPass);
        $this->assertSame($firstPass, $secondPass);
    }

    // M. synthetic ext:* definition proves the intended V1 boundary without
    // a fourth registry: identity claimed first (ExtensionBlockTypeRegistry),
    // then a declarative definition constructed for it — never through
    // ElementRegistry::register(), which 3E-B already rejects for ext:*.
    public function test_m_synthetic_extension_definition_registers_through_the_intended_v1_boundary(): void
    {
        $type = ExtensionBlockTypeRegistry::registerExtensionType('acme', 'demo_block', ['name' => 'Acme Demo']);

        $definition = ThemeBuilderElementDefinition::fromControls(
            type: $type,
            label: 'Acme Demo',
            category: 'content',
            icon: '',
            controls: [],
        );

        $this->assertSame('ext:acme:demo_block', $definition->type);
        $this->assertSame(ThemeBuilderElementDefinition::OWNERSHIP_EXTENSION, $definition->ownership);
        $this->assertFalse(ElementRegistry::has($type), 'An extension identity must never be claimable through ElementRegistry::register() (3E-B).');
        $this->assertTrue(ExtensionBlockTypeRegistry::has($type));
    }

    public function test_m_failed_extension_registration_leaves_no_definition_reachable(): void
    {
        try {
            ExtensionBlockTypeRegistry::registerExtensionType('acme', 'demo_block', ['name' => 'v1']);
            ExtensionBlockTypeRegistry::registerExtensionType('acme', 'demo_block', ['name' => 'v2 — conflicting']);
            $this->fail('Expected rejection of the conflicting re-registration.');
        } catch (\App\Services\ThemeCanvas\Extension\ExtensionIdentityRegistrationException) {
            $this->assertSame('acme', ExtensionBlockTypeRegistry::ownerOf('ext:acme:demo_block'));
        }
    }

    // N. unknown ext:* is safe with no definition, matching 3E-D's existing
    // unknown-type fallback guarantee.
    public function test_n_unregistered_extension_type_has_no_definition_but_is_otherwise_safe(): void
    {
        $this->assertNull(ElementRegistry::definition('ext:someone:not_registered'));
        $this->assertNull(ElementRegistry::ownershipOf('ext:someone:not_registered'));
    }

    // O. registry presence/classification never implies dependency
    // availability or entitlement — the two systems remain orthogonal.
    public function test_o_extension_classification_does_not_imply_dependency_availability(): void
    {
        $type = ExtensionBlockTypeRegistry::registerExtensionType('acme', 'demo_block', ['name' => 'Acme Demo']);
        $definition = ThemeBuilderElementDefinition::fromControls($type, 'Acme Demo', 'content', '', []);
        $this->assertSame(ThemeBuilderElementDefinition::OWNERSHIP_EXTENSION, $definition->ownership);

        config(['extensions.installed' => [], 'extensions.entitlements' => []]);
        $tenant = $this->app->make(TenantContext::class);
        $tenant->set(new \App\Models\License(['license_key' => 'lic-1', 'modules' => []]));
        $resolver = new ExtensionDependencyResolver(new InstalledExtensionProvider(), new ConfigExtensionEntitlementProvider($tenant));

        $this->assertFalse(
            $resolver->resolve('acme/demo-block')->present,
            'Extension identity being registered/classified must never, by itself, satisfy a dependency check.'
        );
    }
}

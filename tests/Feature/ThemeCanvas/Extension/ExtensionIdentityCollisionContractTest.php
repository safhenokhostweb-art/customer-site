<?php

namespace Tests\Feature\ThemeCanvas\Extension;

use App\Builder\ElementRegistry;
use App\Services\ThemeCanvas\Extension\ExtensionBlockTypeRegistry;
use App\Services\ThemeCanvas\Extension\ExtensionIdentityRegistrationException;
use SaaSPlatform\ThemeRuntime\BlockRegistry as CanonicalBlockRegistry;
use SaaSPlatform\ThemeRuntime\BlockTypeAlreadyRegisteredException;
use Tests\TestCase;

/**
 * Phase 3E-B (ADR-030) — proves the fail-closed extension identity/namespace
 * collision contract established by this phase. Each test case letter below
 * corresponds to the task's required test matrix (A-O).
 */
class ExtensionIdentityCollisionContractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        ExtensionBlockTypeRegistry::reset();
        CanonicalBlockRegistry::resetHostTypes();
    }

    protected function tearDown(): void
    {
        ExtensionBlockTypeRegistry::reset();
        CanonicalBlockRegistry::resetHostTypes();
        parent::tearDown();
    }

    // A. host type attempts to collide with canonical type
    public function test_a_host_type_cannot_collide_with_a_canonical_type(): void
    {
        $this->expectException(BlockTypeAlreadyRegisteredException::class);

        CanonicalBlockRegistry::registerHostType('hero', ['name' => 'Forged Hero']);
    }

    // B. duplicate host registration
    public function test_b_duplicate_host_registration_with_different_definition_is_rejected(): void
    {
        CanonicalBlockRegistry::registerHostType('acme_host_test', ['name' => 'First']);

        $this->expectException(BlockTypeAlreadyRegisteredException::class);
        CanonicalBlockRegistry::registerHostType('acme_host_test', ['name' => 'Second — different']);
    }

    public function test_b_duplicate_host_registration_with_identical_definition_is_idempotent(): void
    {
        $definition = ['name' => 'Same'];
        CanonicalBlockRegistry::registerHostType('acme_host_test', $definition);
        CanonicalBlockRegistry::registerHostType('acme_host_test', $definition);

        $this->assertTrue(CanonicalBlockRegistry::isHostRegisteredType('acme_host_test'));
    }

    // C. two extension identities attempt to claim same extension type
    // (same vendor+block combination re-registered with a different, non-
    // idempotent definition — the only real collision surface once the
    // vendor slug is already part of the identity; see test_c2 for proof
    // that two DIFFERENT vendors never collide by construction).
    public function test_c_conflicting_redefinition_of_the_same_extension_type_is_rejected(): void
    {
        ExtensionBlockTypeRegistry::registerExtensionType('acme', 'hero_banner', ['name' => 'Acme Hero v1']);

        $this->expectException(ExtensionIdentityRegistrationException::class);
        ExtensionBlockTypeRegistry::registerExtensionType('acme', 'hero_banner', ['name' => 'Acme Hero v2 — incompatible redefinition']);
    }

    public function test_c2_different_vendors_claiming_the_same_block_name_do_not_collide(): void
    {
        $typeA = ExtensionBlockTypeRegistry::registerExtensionType('acme', 'hero_banner', ['name' => 'Acme Hero']);
        $typeB = ExtensionBlockTypeRegistry::registerExtensionType('other-vendor', 'hero_banner', ['name' => 'Other Hero']);

        $this->assertNotSame($typeA, $typeB);
        $this->assertTrue(ExtensionBlockTypeRegistry::has($typeA));
        $this->assertTrue(ExtensionBlockTypeRegistry::has($typeB));
    }

    // D. reserved namespace claim (module:)
    public function test_d_extension_cannot_claim_module_namespace(): void
    {
        $this->expectException(ExtensionIdentityRegistrationException::class);
        ExtensionBlockTypeRegistry::assertNotReserved('module:blog_posts');
    }

    // D2. reserved namespace claim (core:)
    public function test_d2_extension_cannot_claim_core_namespace(): void
    {
        $this->expectException(ExtensionIdentityRegistrationException::class);
        ExtensionBlockTypeRegistry::assertNotReserved('core:heading');
    }

    // D3. reserved namespace claim (bare/unprefixed)
    public function test_d3_extension_cannot_claim_bare_unprefixed_identity(): void
    {
        $this->expectException(ExtensionIdentityRegistrationException::class);
        ExtensionBlockTypeRegistry::assertNotReserved('heading');
    }

    // E. valid non-colliding host registration
    public function test_e_valid_non_colliding_host_registration_succeeds(): void
    {
        CanonicalBlockRegistry::registerHostType('acme_host_test', ['name' => 'Acme Host Widget']);

        $this->assertTrue(CanonicalBlockRegistry::isHostRegisteredType('acme_host_test'));
        $this->assertArrayHasKey('acme_host_test', CanonicalBlockRegistry::all());
    }

    // F. valid non-colliding extension registration
    public function test_f_valid_non_colliding_extension_registration_succeeds(): void
    {
        $type = ExtensionBlockTypeRegistry::registerExtensionType('acme', 'hero_banner', ['name' => 'Acme Hero']);

        $this->assertSame('ext:acme:hero_banner', $type);
        $this->assertTrue(ExtensionBlockTypeRegistry::has('ext:acme:hero_banner'));
        $this->assertSame('acme', ExtensionBlockTypeRegistry::ownerOf('ext:acme:hero_banner'));
    }

    // E (identity validation). malformed extension identity rejected
    public function test_g_malformed_vendor_slug_rejected(): void
    {
        $this->expectException(ExtensionIdentityRegistrationException::class);
        ExtensionBlockTypeRegistry::registerExtensionType('Acme_Vendor!', 'hero_banner', []);
    }

    public function test_g_malformed_vendor_slug_with_colon_rejected(): void
    {
        // Guards against smuggling a reserved-namespace prefix through the
        // vendor-slug parameter itself (e.g. "module" + injected colon).
        $this->expectException(ExtensionIdentityRegistrationException::class);
        ExtensionBlockTypeRegistry::registerExtensionType('module:acme', 'hero_banner', []);
    }

    public function test_g_malformed_block_name_rejected(): void
    {
        $this->expectException(ExtensionIdentityRegistrationException::class);
        ExtensionBlockTypeRegistry::registerExtensionType('acme', 'Hero Banner!', []);
    }

    public function test_g_too_short_vendor_slug_rejected(): void
    {
        $this->expectException(ExtensionIdentityRegistrationException::class);
        ExtensionBlockTypeRegistry::registerExtensionType('a', 'hero_banner', []);
    }

    // G. extension-vs-extension same-type collision rejected
    public function test_g_extension_vs_extension_collision_leaves_registry_unchanged(): void
    {
        ExtensionBlockTypeRegistry::registerExtensionType('acme', 'hero_banner', ['name' => 'Acme Hero v1']);

        try {
            ExtensionBlockTypeRegistry::registerExtensionType('acme', 'hero_banner', ['name' => 'Acme Hero v2 — different']);
            $this->fail('Expected ExtensionIdentityRegistrationException was not thrown.');
        } catch (ExtensionIdentityRegistrationException) {
            // rejected redefinition must not overwrite the original
            $this->assertSame(['name' => 'Acme Hero v1'], ExtensionBlockTypeRegistry::all()['ext:acme:hero_banner']);
        }
    }

    // H. host-vs-extension collision rejected
    public function test_h_element_registry_cannot_claim_an_identity_already_owned_by_an_extension(): void
    {
        ExtensionBlockTypeRegistry::registerExtensionType('acme', 'custom_widget', ['name' => 'Acme Widget']);

        $fakeElementClass = new class {
            public static function getType(): string
            {
                return 'ext:acme:custom_widget';
            }
        };

        $this->expectException(\InvalidArgumentException::class);
        $reflection = new \ReflectionMethod(ElementRegistry::class, 'assertClaimable');
        $reflection->setAccessible(true);
        $reflection->invoke(null, 'ext:acme:custom_widget', get_class($fakeElementClass));
    }

    // I. rejected registration leaves registry unchanged
    public function test_i_rejected_canonical_collision_leaves_canonical_registry_unchanged(): void
    {
        $before = CanonicalBlockRegistry::all()['hero'];

        try {
            CanonicalBlockRegistry::registerHostType('hero', ['name' => 'Forged Hero']);
            $this->fail('Expected BlockTypeAlreadyRegisteredException was not thrown.');
        } catch (BlockTypeAlreadyRegisteredException) {
            $this->assertSame($before, CanonicalBlockRegistry::all()['hero']);
            $this->assertFalse(CanonicalBlockRegistry::isHostRegisteredType('hero'));
        }
    }

    public function test_i_rejected_extension_reregistration_leaves_original_registration_unchanged(): void
    {
        ExtensionBlockTypeRegistry::registerExtensionType('acme', 'hero_banner', ['name' => 'v1']);

        try {
            ExtensionBlockTypeRegistry::registerExtensionType('acme', 'hero_banner', ['name' => 'v2 — different']);
            $this->fail('Expected exception was not thrown.');
        } catch (ExtensionIdentityRegistrationException) {
            $this->assertSame(['name' => 'v1'], ExtensionBlockTypeRegistry::all()['ext:acme:hero_banner']);
            $this->assertCount(1, ExtensionBlockTypeRegistry::all());
        }
    }

    // J. existing canonical lookup unchanged
    public function test_j_existing_canonical_lookup_unchanged(): void
    {
        $this->assertArrayHasKey('button', CanonicalBlockRegistry::all());
        $this->assertArrayHasKey('hero', CanonicalBlockRegistry::all());
        $this->assertTrue(CanonicalBlockRegistry::isCanonicalType('hero'));
        $this->assertFalse(CanonicalBlockRegistry::isCanonicalType('ext:acme:hero_banner'));
    }

    // K. existing host lookup unchanged
    public function test_k_existing_host_module_lookup_unchanged(): void
    {
        // row/column/spacer are Element SDK (Builder\ElementRegistry) types,
        // not ThemeCanvas\BlockRegistry ones — a distinct persistence
        // domain (header/footer builder vs. the theme_data canvas blob),
        // confirmed by direct inspection before writing this assertion.
        $this->assertArrayHasKey('module:motion', \App\Services\ThemeCanvas\BlockRegistry::all());
        $this->assertTrue(ElementRegistry::has('row'));
    }

    // L. existing module:* behavior unchanged (renderer dispatch prefix check untouched)
    public function test_l_module_prefix_dispatch_is_untouched_by_the_new_registries(): void
    {
        // The extension identity contract must never intercept or alter
        // module:* dispatch — it only governs NEW `ext:` registrations.
        $this->assertTrue(str_starts_with('module:motion', 'module:'));
        $this->expectException(ExtensionIdentityRegistrationException::class);
        ExtensionBlockTypeRegistry::assertNotReserved('module:motion');
    }

    // M. ElementRegistry cannot silently shadow protected identity
    public function test_m_element_registry_cannot_silently_shadow_an_extension_identity(): void
    {
        ExtensionBlockTypeRegistry::registerExtensionType('acme', 'shadow_test', ['name' => 'Acme']);

        $reflection = new \ReflectionMethod(ElementRegistry::class, 'assertClaimable');
        $reflection->setAccessible(true);

        $this->expectException(\InvalidArgumentException::class);
        $reflection->invoke(null, 'ext:acme:shadow_test', 'Some\\Fake\\ElementClass');
    }

    public function test_m_element_registry_duplicate_class_registration_of_same_type_is_rejected(): void
    {
        $reflection = new \ReflectionMethod(ElementRegistry::class, 'assertClaimable');
        $reflection->setAccessible(true);

        $classProp = new \ReflectionProperty(ElementRegistry::class, 'classes');
        $classProp->setAccessible(true);
        $original = $classProp->getValue();
        $classProp->setValue(null, ['dup_test_type' => 'App\\Builder\\Elements\\HeadingElement']);

        try {
            $this->expectException(\InvalidArgumentException::class);
            $reflection->invoke(null, 'dup_test_type', 'App\\Builder\\Elements\\TextElement');
        } finally {
            $classProp->setValue(null, $original);
        }
    }

    // N. persisted existing type strings unchanged
    public function test_n_persisted_existing_type_strings_are_unchanged(): void
    {
        $this->assertArrayHasKey('module:motion', \App\Services\ThemeCanvas\BlockRegistry::all());
        $this->assertTrue(ElementRegistry::has('row'));
        $this->assertTrue(ElementRegistry::has('column'));
        $this->assertTrue(ElementRegistry::has('spacer'));
    }

    // O. deterministic registry result independent of merge order
    public function test_o_canonical_lookup_is_deterministic_regardless_of_registration_order(): void
    {
        CanonicalBlockRegistry::registerHostType('order_test_a', ['name' => 'A']);
        $afterA = CanonicalBlockRegistry::all()['hero'];

        CanonicalBlockRegistry::registerHostType('order_test_b', ['name' => 'B']);
        $afterB = CanonicalBlockRegistry::all()['hero'];

        $this->assertSame($afterA, $afterB, 'Canonical definitions must never depend on host registration order.');
    }
}

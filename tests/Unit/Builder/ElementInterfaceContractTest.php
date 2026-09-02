<?php

namespace Tests\Unit\Builder;

use App\Builder\Contracts\ElementInterface;
use App\Builder\Contracts\HasAssets;
use App\Builder\Contracts\HasContainerCapability;
use App\Builder\Contracts\HasDynamicData;
use App\Builder\Contracts\HasValidation;
use App\Builder\Definition\ThemeBuilderElementDefinition;
use App\Builder\ElementRegistry;
use App\Builder\Elements\BaseElement;
use App\Builder\Rendering\ElementRenderer;
use App\Builder\Services\DynamicDataResolver;
use App\Services\ThemeCanvas\Extension\ExtensionBlockTypeRegistry;
use Tests\Unit\Builder\Extension\Fixtures\DemoCardElement;

/**
 * Phase 3E-G (correction) — proves the ElementInterface/runtime contract
 * mismatch found during 3E-G review is fully resolved: ElementRenderer/
 * ElementRegistry never again call a method ElementInterface does not
 * guarantee, BaseElement (and all 13 built-ins) still satisfy it, and a
 * standalone (non-BaseElement) renderer can rely on the interface alone.
 */
class ElementInterfaceContractTest extends \Tests\TestCase
{
    private const ALL_13_TYPES = [
        'heading', 'text', 'image', 'button', 'divider', 'spacer',
        'section', 'row', 'column', 'container', 'grid', 'columns', 'hero',
    ];

    // A. ElementInterface and runtime unconditional calls are contract-consistent
    public function test_a_element_interface_declares_every_method_called_unconditionally(): void
    {
        $methods = array_map(
            static fn (\ReflectionMethod $m) => $m->getName(),
            (new \ReflectionClass(ElementInterface::class))->getMethods()
        );

        $this->assertContains('getRequiredModule', $methods);
        $this->assertContains('getDefaultSize', $methods);

        // supportsDynamicData is deliberately NOT part of the mandatory
        // contract — it remains HasDynamicData's optional capability.
        $this->assertNotContains('supportsDynamicData', $methods);
    }

    public function test_a_element_renderer_no_longer_calls_supports_dynamic_data_unconditionally(): void
    {
        $source = file_get_contents((new \ReflectionClass(ElementRenderer::class))->getFileName());
        $this->assertMatchesRegularExpression(
            '/is_subclass_of\(\$class,\s*HasDynamicData::class\)\s*&&\s*\$class::supportsDynamicData\(\)/',
            $source,
            'ElementRenderer must gate supportsDynamicData() behind HasDynamicData, not call it unconditionally.'
        );
    }

    // B. BaseElement satisfies corrected ElementInterface
    public function test_b_base_element_satisfies_the_corrected_interface(): void
    {
        $this->assertTrue((new \ReflectionClass(BaseElement::class))->implementsInterface(ElementInterface::class));
        $this->assertTrue(method_exists(BaseElement::class, 'getRequiredModule'));
        $this->assertTrue(method_exists(BaseElement::class, 'getDefaultSize'));
    }

    // C. all 13 built-ins satisfy corrected contract
    public function test_c_all_13_built_ins_satisfy_the_corrected_contract(): void
    {
        foreach (self::ALL_13_TYPES as $type) {
            $class = ElementRegistry::get($type);
            $this->assertNotNull($class, "{$type} must be registered");
            $this->assertTrue(is_subclass_of($class, ElementInterface::class), "{$type} must implement ElementInterface");
            $this->assertIsString($class::getRequiredModule() ?? '', "{$type}::getRequiredModule() must be callable");
            $this->assertIsString($class::getDefaultSize(), "{$type}::getDefaultSize() must be callable");
        }
    }

    // D. standalone non-BaseElement extension implementation registers (sanity re-confirmation)
    public function test_d_standalone_fixture_does_not_extend_base_element(): void
    {
        $this->assertFalse(is_subclass_of(DemoCardElement::class, BaseElement::class));
        $this->assertTrue(is_subclass_of(DemoCardElement::class, ElementInterface::class));
    }

    // F. no undefined-method failure occurs for a standalone renderer
    public function test_f_standalone_renderer_calls_do_not_error(): void
    {
        $this->assertNull(DemoCardElement::getRequiredModule());
        $this->assertSame('full', DemoCardElement::getDefaultSize());
        $this->assertIsString(DemoCardElement::render(DemoCardElement::getDefaultProps(), ['id' => 'f1']));
    }

    // G. required-module behavior unchanged for built-ins
    public function test_g_required_module_behavior_unchanged(): void
    {
        foreach (ElementRegistry::all() as $type => $meta) {
            $this->assertNull($meta['required_module'], "{$type} should require no module, matching pre-correction behavior.");
        }
    }

    // H. default-size behavior unchanged for built-ins
    public function test_h_default_size_behavior_unchanged(): void
    {
        foreach (ElementRegistry::all() as $type => $meta) {
            $this->assertSame('full', $meta['defaultSize'], "{$type} should default to 'full', matching pre-correction behavior.");
        }
    }

    // I. dynamic-data behavior unchanged for built-ins (still false for all 13)
    public function test_i_dynamic_data_behavior_unchanged_for_built_ins(): void
    {
        foreach (self::ALL_13_TYPES as $type) {
            $definition = ThemeBuilderElementDefinition::fromElementClass(ElementRegistry::get($type));
            $this->assertFalse($definition->supportsDynamicData, "{$type} should not support dynamic data, matching pre-correction behavior.");
        }
    }

    public function test_i_dynamic_data_resolver_is_never_invoked_for_a_class_without_has_dynamic_data(): void
    {
        // DemoCardElement does not implement HasDynamicData — rendering
        // with a resolver set must leave a {{token}}-shaped prop untouched
        // (proving the resolver path was correctly skipped, not merely
        // that no token happened to match). Registered here directly
        // (bypassing ThemeBuilderExtensionDefinition) since this test only
        // needs the renderer class reachable via ElementRegistry::get().
        ExtensionBlockTypeRegistry::registerExtensionType('acme', 'demo_card', ['name' => 'Acme Demo Card']);
        ElementRegistry::registerExtensionElement('ext:acme:demo_card', DemoCardElement::class);

        try {
            $renderer = (new ElementRenderer())->setDynamicDataResolver(new DynamicDataResolver());
            $html = $renderer->render('ext:acme:demo_card', ['title' => '{{site.name}}', 'content' => ''], ['id' => 'i1']);
            $this->assertStringContainsString('{{site.name}}', $html);
        } finally {
            ExtensionBlockTypeRegistry::reset();
            ElementRegistry::clearCache();
        }
    }

    // J. optional capability interfaces remain optional where intended
    public function test_j_optional_capabilities_remain_outside_the_mandatory_interface(): void
    {
        $methods = array_map(
            static fn (\ReflectionMethod $m) => $m->getName(),
            (new \ReflectionClass(ElementInterface::class))->getMethods()
        );

        $this->assertNotContains('getAssets', $methods, 'getAssets stays HasAssets-gated.');
        $this->assertNotContains('supportsDynamicData', $methods, 'supportsDynamicData stays HasDynamicData-gated.');
        $this->assertNotContains('isContainer', $methods, 'isContainer stays HasContainerCapability-gated.');
        $this->assertNotContains('getAllowedChildren', $methods, 'getAllowedChildren stays HasContainerCapability-gated.');
        $this->assertNotContains('validate', $methods, 'validate stays HasValidation-gated (never called unconditionally).');

        // Sanity: the optional interfaces themselves still exist and are distinct.
        $this->assertTrue(interface_exists(HasAssets::class));
        $this->assertTrue(interface_exists(HasDynamicData::class));
        $this->assertTrue(interface_exists(HasContainerCapability::class));
        $this->assertTrue(interface_exists(HasValidation::class));
    }
}

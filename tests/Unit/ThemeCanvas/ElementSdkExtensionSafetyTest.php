<?php

namespace Tests\Unit\ThemeCanvas;

use App\Builder\Contracts\ElementInterface;
use App\Builder\ElementRegistry;
use App\Builder\Elements\BaseElement;
use App\Builder\Rendering\ElementRenderer;
use App\Services\ThemeCanvas\ThemeCanvasRenderer;
use Tests\TestCase;

/**
 * Phase 2j Section 12 — proves the genuine, non-core Element SDK/extension
 * capabilities this phase deliberately preserved (not blindly deleted)
 * still work after reconciling the 10 canonical collisions.
 */
class ElementSdkExtensionSafetyTest extends TestCase
{
    public function test_a_host_only_element_with_no_canonical_equivalent_still_renders_directly(): void
    {
        // RowElement/ColumnElement/SpacerElement are registered but have no
        // canonical shared BlockRenderer method — genuinely host-only.
        $html = \App\Builder\Elements\SpacerElement::render(['height' => 40], ['id' => 'sp1']);
        $this->assertNotSame('', trim($html));
    }

    public function test_an_unregistered_type_falls_back_to_the_hosts_own_safe_placeholder(): void
    {
        $renderer = new \App\Services\ThemeBuilder\BlockRenderer();
        $renderer->setSchemaRegistry(new \App\Services\ThemeBuilder\ElementSchemaRegistry());
        $html = $renderer->renderBlock(['type' => 'totally_unknown_type_xyz', 'id' => 'u1', 'props' => []]);
        // Unknown type with no content renders nothing (host's own renderFallback
        // contract — see its #[HostOwnedOverride] reason) rather than crashing.
        $this->assertIsString($html);
    }

    public function test_module_dispatch_is_unaffected_by_element_sdk_reconciliation(): void
    {
        $renderer = app(ThemeCanvasRenderer::class);
        $html = $renderer->renderBlock(['type' => 'module:unknown_module_type', 'id' => 'm1', 'props' => []]);
        $this->assertIsString($html);
    }

    public function test_element_registry_all_returns_the_configured_elements(): void
    {
        $all = ElementRegistry::all();
        $this->assertArrayHasKey('hero', $all);
        $this->assertArrayHasKey('row', $all);
        $this->assertCount(13, $all, 'config/builder.php element count changed — verify no new canonical collision was introduced.');
    }

    public function test_a_new_extension_element_can_register_and_render_without_touching_canonical(): void
    {
        // Simulates a future custom/extension-owned element type — proves
        // the registration mechanism itself (not the specific 13 configured
        // classes) still works generically.
        $class = new class extends BaseElement {
            public static function getType(): string { return 'custom_extension_widget_test'; }
            public static function getName(): string { return 'Test Widget'; }
            public static function getIcon(): string { return '★'; }
            public static function getCategory(): string { return 'content'; }
            public static function getDefaultProps(): array { return []; }
            public static function getSchema(): array { return []; }
            public static function render(array $props, array $context = []): string
            {
                return '<div class="custom-extension-widget">extension content</div>';
            }
        };

        ElementRegistry::register(get_class($class));
        $this->assertTrue(ElementRegistry::has('custom_extension_widget_test'));

        $html = app(ElementRenderer::class)->render('custom_extension_widget_test', [], ['id' => 'ext1']);
        $this->assertStringContainsString('extension content', $html);
    }

    public function test_element_validator_never_throws_on_render_failure(): void
    {
        // ElementRenderer::render() must never let an element's own bug
        // reach the caller as an uncaught exception.
        $class = new class extends BaseElement {
            public static function getType(): string { return 'throwing_extension_test'; }
            public static function getName(): string { return 'Throws'; }
            public static function getIcon(): string { return '!'; }
            public static function getCategory(): string { return 'content'; }
            public static function getDefaultProps(): array { return []; }
            public static function getSchema(): array { return []; }
            public static function render(array $props, array $context = []): string
            {
                throw new \RuntimeException('simulated extension bug');
            }
        };
        ElementRegistry::register(get_class($class));

        $html = app(ElementRenderer::class)->render('throwing_extension_test', [], ['id' => 'ext2']);
        $this->assertStringContainsString('tb-placeholder-error', $html);
    }
}

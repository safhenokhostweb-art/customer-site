<?php

namespace Tests\Feature\ThemeCanvas;

use App\Services\ThemeBuilder\ElementSchemaRegistry;
use App\Services\ThemeCanvas\BlockRegistry;
use SaaSPlatform\ThemeRuntime\Inspector\InspectorFieldRegistry;
use SaaSPlatform\ThemeRuntime\Widget\WidgetSchemaContract;
use Tests\TestCase;

/**
 * ADR-082 (saas-platform) on the customer host: the customer Canvas editor
 * renders every Inspector tab from the schema through the shared
 * theme-runtime controls (canvas.controls + control-* partials, the
 * CanvasControls mixin), the host registry keeps the extended contract, the
 * header's generic Style sections are hidden, and the host validator types
 * the new controls and a responsive field's device values.
 */
class Adr082InspectorControlsHostTest extends TestCase
{
    public function test_the_host_registry_meets_the_extended_contract(): void
    {
        $this->assertSame([], WidgetSchemaContract::violations(BlockRegistry::all()));

        $header = InspectorFieldRegistry::normalizeDefinition(BlockRegistry::all()['nav_header']);
        $this->assertSame(array_keys(InspectorFieldRegistry::GENERIC_STYLE_SECTIONS), $header['editor']['hideGenericStyle']);
        $this->assertSame('style', $header['schema']['bg_color']['panel']);
        $this->assertSame('advanced', $header['schema']['sticky']['panel']);
        $this->assertSame('content', $header['schema']['logo_image']['panel']);
    }

    public function test_the_customer_editor_renders_every_tab_through_the_shared_controls(): void
    {
        $editor = (string) file_get_contents(resource_path('views/admin/theme-design/canvas-editor.blade.php'));
        $this->assertStringContainsString("@include('theme-runtime::canvas.controls')", $editor);
        $this->assertStringContainsString('...window.CanvasControls.editorMixin(),', $editor);
        $this->assertStringContainsString('ctlSetProp(key, value) {', $editor);
        $this->assertStringContainsString('x-for="(schemaDef, propKey) in panelFields(selectedTab)"', $editor);
        $this->assertStringNotContainsString('in selectedSchema"', $editor, 'the field loop is filtered per tab');
        foreach (['control-states', 'control-devices', 'control-field'] as $partial) {
            $this->assertStringContainsString("@include('theme-runtime::canvas.{$partial}')", $editor);
        }
        foreach (array_keys(InspectorFieldRegistry::GENERIC_STYLE_SECTIONS) as $section) {
            $this->assertStringContainsString("x-show=\"genericStyleVisible('{$section}')\"", $editor, $section);
        }
        $this->assertStringContainsString("schemaDef.type === 'number' && !ctlIsResponsive(propKey)", $editor);
        $this->assertStringContainsString("selectedTab === 'content' && isContainerType(selectedBlock?.type)", $editor, 'the children panel stays on the content tab');

        $view = $this->app['view'];
        $this->assertStringContainsString('root.CanvasControls = api;', $view->make('theme-runtime::canvas.controls')->render());
        $this->assertStringContainsString("schemaDef.type === 'spacing'", $view->make('theme-runtime::canvas.control-field')->render());
    }

    /**
     * Step 2: the header's layout left its inline <style> for the package's
     * header sheet. Both storefront paths must still ship it, in the place
     * the inline style held: after every other stylesheet.
     */
    public function test_both_storefront_layouts_ship_the_header_sheet_last(): void
    {
        $header = trim((string) file_get_contents(\SaaSPlatform\ThemeRuntime\Providers\ThemeRuntimeServiceProvider::cssHeaderPath()));
        $this->assertStringEndsWith($header, trim($this->app['view']->make('theme-runtime::css.blocks')->render()), 'canvas pages: the shared block view ends with it');
        $partial = (string) file_get_contents(resource_path('views/partials/theme-canvas-style-contract.blade.php'));
        $this->assertStringContainsString("@include('theme-runtime::css.blocks')", $partial);

        $legacy = (string) file_get_contents(resource_path('views/themes/default/layout.blade.php'));
        $head = substr($legacy, 0, (int) strpos($legacy, '</head>'));
        $include = strrpos($head, "@include('theme-runtime::css.header')");
        $this->assertNotFalse($include, 'the legacy storefront layout renders nav_header without the block sheet');
        $after = substr($head, $include);
        $this->assertStringNotContainsString('<style', $after, 'no stylesheet after the header sheet in <head>');
        $this->assertStringNotContainsString('stylesheet', $after);
        $this->assertStringNotContainsString("@include('theme-runtime::css.blocks')", $head);
    }

    public function test_the_validator_types_the_new_controls_and_device_values(): void
    {
        $validator = app(ElementSchemaRegistry::class);
        $this->assertTrue($validator->validate(['type' => 'nav_header', 'props' => [
            'logo_height' => 44, 'logo_height_mobile' => 30, 'sticky' => true, 'bg_color' => '#ffffff',
        ]]), implode('; ', $validator->getLastErrors()));
        $this->assertFalse($validator->validate(['type' => 'nav_header', 'props' => ['logo_height' => 'tall']]), 'a range is numeric');
    }
}

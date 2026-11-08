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

    /** Step 3: the header Style tab on this host: valid props pass, and the renderer scopes them. */
    public function test_header_style_props_validate_and_render_as_scoped_values_on_this_host(): void
    {
        $props = [
            'min_height' => 84, 'min_height_mobile' => 62,
            'padding' => ['unit' => 'px', 'linked' => false, 'block-start' => 8, 'block-end' => 8, 'inline-start' => 24, 'inline-end' => 24],
            'menu_typography' => ['size' => 16, 'weight' => '700'], 'menu_typography_mobile' => ['size' => 14],
            'header_shadow' => ['x' => 0, 'y' => 4, 'blur' => 12, 'spread' => -2, 'color' => 'rgba(15, 23, 42, 0.12)', 'inset' => false],
            'menu_color_hover' => '#2563eb', 'pointer' => 'framed', 'backdrop_blur' => 10,
        ];
        $validator = app(ElementSchemaRegistry::class);
        $this->assertTrue($validator->validate(['type' => 'nav_header', 'props' => $props]), implode('; ', $validator->getLastErrors()));
        $this->assertFalse($validator->validate(['type' => 'nav_header', 'props' => ['padding' => 'wide']]), 'spacing is structured');

        $html = (new \App\Services\ThemeBuilder\BlockRenderer())->setMode('public')->renderBlock(['type' => 'nav_header', 'id' => 'h1', 'props' => $props + ['logo_text' => 'برند']]);
        $this->assertStringStartsWith('<style data-tb-hdr="tb-h1">#tb-h1{--tb-hdr-min-h:84px;', ltrim($html));
        $this->assertStringContainsString('@media(max-width:767.98px){#tb-h1{--tb-hdr-min-h-m:62px;--tb-hdr-menu-size:14px}}', $html);
        $this->assertMatchesRegularExpression('/<div[^>]*\sid="tb-h1"[^>]*class="tb-nav-header tb-nav-header--pointer-framed"[^>]*data-tb-hdr-set="shadow blur menu-size menu-weight menu-color-hover"/', $html);
    }

    /** Step 4: the Advanced behaviour and drawer props validate, and the renderer carries them on this host. */
    public function test_header_behaviour_and_drawer_props_validate_and_render_on_this_host(): void
    {
        $props = [
            'sticky' => true, 'sticky_devices' => 'desktop', 'sticky_offset' => 24, 'scroll_threshold' => 120,
            'sticky_shrink' => false, 'scrolled_bg_color' => '#0f172a', 'scrolled_text_color' => '#f8fafc',
            'scrolled_shadow' => ['x' => 0, 'y' => 6, 'blur' => 18, 'spread' => 0, 'color' => 'rgba(0, 0, 0, 0.2)', 'inset' => false],
            'burger_breakpoint' => 'tablet', 'drawer_style' => 'side', 'drawer_side' => 'end',
            'drawer_width' => ['size' => 80, 'unit' => '%'], 'drawer_bg' => '#111827', 'burger_color' => '#e11d48', 'burger_size' => 30,
        ];
        $validator = app(ElementSchemaRegistry::class);
        $this->assertTrue($validator->validate(['type' => 'nav_header', 'props' => $props]), implode('; ', $validator->getLastErrors()));
        $this->assertFalse($validator->validate(['type' => 'nav_header', 'props' => ['drawer_width' => 80]]), 'a multi-unit range is {size, unit}');

        $html = (new \App\Services\ThemeBuilder\BlockRenderer())->setMode('public')->renderBlock(['type' => 'nav_header', 'id' => 'h4', 'props' => $props + ['logo_text' => 'برند']]);
        $this->assertMatchesRegularExpression('/data-tb-header="[^"]*noshrink/', $html);
        foreach (['data-tb-scroll-threshold="120"', 'data-tb-sticky-offset="24"', 'data-tb-sticky-on="desktop"', 'data-tb-burger="tablet"', 'tb-nav-header--drawer-end', '--tb-hdr-drawer-w:80%', '--tb-hdr-burger-size:30px'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
        $this->assertMatchesRegularExpression('/data-tb-hdr-set="[^"]*scrolled-bg scrolled-text scrolled-shadow/', $html);

        $editor = file_get_contents(resource_path('views/admin/theme-design/canvas-editor.blade.php'));
        $this->assertStringContainsString(':checked="ctlChecked(propKey)"', $editor, 'a toggle shows its schema default (sticky_shrink is on by default)');
    }

    /** Step 5: accessibility props validate on this host; an empty menu renders no burger or navigation. */
    public function test_header_accessibility_props_and_the_empty_menu_on_this_host(): void
    {
        $props = ['menu_aria_label' => 'منوی فروشگاه', 'focus_color' => '#f59e0b', 'sticky' => true, 'z_index' => 1200];
        $validator = app(ElementSchemaRegistry::class);
        $this->assertTrue($validator->validate(['type' => 'nav_header', 'props' => $props]), implode('; ', $validator->getLastErrors()));
        $this->assertFalse($validator->validate(['type' => 'nav_header', 'props' => ['z_index' => 'top']]), 'z-index is a whole number');

        $render = fn (array $p) => (new \App\Services\ThemeBuilder\BlockRenderer())->setMode('public')->renderBlock(['type' => 'nav_header', 'id' => 'h5', 'props' => $p + ['logo_text' => 'برند']]);
        $html = $render($props);
        $this->assertStringContainsString('<nav class="tb-nav-header-links" aria-label="منوی فروشگاه"', $html);
        $this->assertStringContainsString('--tb-hdr-focus:#f59e0b', $html);
        $this->assertStringContainsString('data-tb-z="1200"', $html);

        $topBar = $render(['nav_links' => [['label' => null, 'url' => '/'], ['label' => '', 'url' => '/shop']], 'cta_text' => 'شروع کنید']);
        $markup = substr($topBar, 0, (int) strpos($topBar, '<script'));
        $this->assertStringNotContainsString('<nav', $markup);
        $this->assertStringNotContainsString('tb-nav-header-toggle', $markup, 'no burger that opens an empty drawer');
        $this->assertStringContainsString('tb-nav-header-cta', $markup);
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

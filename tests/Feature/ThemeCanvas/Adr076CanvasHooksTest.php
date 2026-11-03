<?php

namespace Tests\Feature\ThemeCanvas;

use App\Services\ThemeBuilder\BlockRenderer;
use App\Services\ThemeBuilder\ElementSchemaRegistry;
use App\Services\ThemeCanvas\BlockRegistry;
use SaaSPlatform\ThemeRuntime\Widget\WidgetSchemaContract;
use Tests\TestCase;

/**
 * ADR-076 on the customer host: the host wrapper override emits the same
 * data-widget-type hook as the package, the host registry meets the widget
 * schema contract, the new header/footer props pass the host validator, and
 * the customer Canvas editor uses the shared page-tab strip and the schema
 * `ui: segmented` / `group` controls.
 */
class Adr076CanvasHooksTest extends TestCase
{
    public function test_the_host_wrapper_emits_data_widget_type_in_public_and_editor_modes(): void
    {
        $block = ['type' => 'heading', 'id' => 'h1', 'props' => ['text' => 'سلام']];

        $public = (new BlockRenderer())->setMode('public')->renderBlock($block);
        $this->assertStringContainsString('data-tb-block-type="heading" data-widget-type="heading"', $public);

        $editor = (new BlockRenderer())->setMode('editor')->setContext(['editor_mode' => true])->renderBlock($block);
        $this->assertStringContainsString('data-tb-block-id="h1" data-tb-block-type="heading" data-widget-type="heading"', $editor);
    }

    public function test_the_host_registry_meets_the_widget_schema_contract(): void
    {
        $this->assertSame([], WidgetSchemaContract::violations(BlockRegistry::all()));
    }

    public function test_the_new_header_and_footer_props_pass_the_host_validator(): void
    {
        $validator = app(ElementSchemaRegistry::class);
        $header = ['type' => 'nav_header', 'props' => [
            'logo_image' => ['media_id' => 'abc'], 'logo_alt' => 'لوگو', 'logo_height' => 48, 'logo_height_mobile' => 30,
            'menu_align' => 'center', 'social_style' => 'brand', 'social_show_mobile' => true,
            'social_links' => [['url' => 'https://eitaa.com/x', 'network' => 'eitaa', 'label' => '']],
            'bg_color' => 'var(--tb-surface)', 'text_color' => '#111827',
        ]];
        $this->assertTrue($validator->validate($header), implode('; ', $validator->getLastErrors()));

        $footer = ['type' => 'nav_footer', 'props' => [
            'social_style' => 'mono', 'social_links' => [['url' => 'https://splus.ir/x', 'network' => 'soroush'], ['url' => 'https://gap.im/x', 'network' => 'gap']],
        ]];
        $this->assertTrue($validator->validate($footer), implode('; ', $validator->getLastErrors()));
    }

    public function test_the_customer_editor_uses_the_shared_tab_strip_and_schema_controls(): void
    {
        $editor = file_get_contents(resource_path('views/admin/theme-design/canvas-editor.blade.php'));
        $this->assertStringContainsString("@include('theme-runtime::canvas.tab-strip')", $editor);
        $this->assertStringContainsString('<div class="tbts" data-tb-tabstrip data-testid="tb-page-tabs">', $editor);
        $this->assertSame(2, substr_count($editor, "@include('theme-runtime::canvas.tab-strip-nav'"));
        $this->assertStringContainsString(':data-canvas-page-tab="key"', $editor);
        $this->assertStringContainsString(":aria-current=\"currentPage === key ? 'page' : null\"", $editor, 'the strip keeps the active tab in view by aria-current');
        $this->assertStringContainsString("schemaDef.type === 'select' && schemaDef.ui === 'segmented'", $editor);
        $this->assertStringContainsString("schemaDef.type === 'select' && schemaDef.ui !== 'segmented'", $editor);
        $this->assertStringContainsString('fieldGroupStart(propKey)', $editor);
        $this->assertStringContainsString(':min="schemaDef.min ?? 0" :max="schemaDef.max ?? null"', $editor);
    }
}

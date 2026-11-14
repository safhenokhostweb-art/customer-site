<?php

namespace Tests\Feature\SolarCalculator;

use App\Modules\SolarCalculator\Canvas\SolarCalculatorWidget;
use App\Modules\SolarCalculator\Services\SolarCalculatorSettings;
use App\Modules\SolarCalculator\Support\SolarCalculatorDefaults;
use App\Services\ThemeCanvas\BlockRegistry as HostRegistry;
use App\Services\ThemeCanvas\ThemeCanvasRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SaaSPlatform\ThemeRuntime\BlockRegistry as CanonicalRegistry;
use Tests\Feature\SolarCalculator\Concerns\SolarCalculatorFixtures;
use Tests\TestCase;

class SolarCalculatorWidgetTest extends TestCase
{
    use RefreshDatabase;
    use SolarCalculatorFixtures;

    /** Same list as CanonicalBlockDefinitionParityTest::EDITABLE_TYPES. */
    private const EDITABLE_TYPES = ['string', 'text', 'textarea', 'rich_text', 'link', 'image', 'repeater', 'array',
        'number', 'motion', 'boolean', 'select', 'color', 'blocks', 'motion_params', 'icon', 'range', 'spacing', 'typography', 'shadow'];

    private function render(array $props = []): string
    {
        return (new ThemeCanvasRenderer())->renderBlock(['id' => 'sc1', 'type' => SolarCalculatorWidget::TYPE, 'props' => $props]);
    }

    public function test_the_widget_is_not_in_any_shared_registry(): void
    {
        $this->assertNull(CanonicalRegistry::get(SolarCalculatorWidget::TYPE), 'never in the platform catalog');
        $this->assertArrayNotHasKey(SolarCalculatorWidget::TYPE, HostRegistry::all(), 'host registry parity holds');
    }

    public function test_every_ui_text_and_image_is_an_editable_canvas_field(): void
    {
        $def = SolarCalculatorWidget::definition();

        foreach (array_keys(SolarCalculatorDefaults::UI_FIELDS) as $key) {
            $this->assertArrayHasKey($key, $def['schema'], "{$key} missing from the canvas schema");
            $this->assertSame('', $def['defaultProps'][$key], 'empty prop inherits the module setting');
        }
        foreach ($def['schema'] as $key => $field) {
            $this->assertContains($field['type'], self::EDITABLE_TYPES, "{$key} has no canvas control");
            $this->assertNotEmpty($field['group'] ?? null, "{$key} has no inspector group");
        }
        $this->assertSame('solar_calculator', $def['requires']);
    }

    public function test_renders_nothing_publicly_on_an_unlicensed_site(): void
    {
        $this->licenseModules(['shop']);

        $this->assertStringNotContainsString('data-sc-calc', $this->render());
    }

    public function test_renders_module_settings_and_block_overrides_win(): void
    {
        $this->licenseModules();
        $settings = app(SolarCalculatorSettings::class);
        $settings->save('ui', array_replace(SolarCalculatorDefaults::uiDefaults(), [
            'title' => 'عنوان از تنظیمات ماژول',
            'subtitle' => 'توضیح ماژول',
        ]));

        $html = $this->render(['title' => 'عنوان مخصوص این صفحه', 'subtitle' => '']);

        $this->assertStringContainsString('data-sc-calc', $html);
        $this->assertStringContainsString('عنوان مخصوص این صفحه', $html);
        $this->assertStringNotContainsString('عنوان از تنظیمات ماژول', $html);
        $this->assertStringContainsString('توضیح ماژول', $html, 'an empty prop inherits the setting');
        $this->assertStringContainsString('id="calc-tool"', $html, 'the solar page template links #calc-tool');
        $this->assertStringContainsString('تهران', $html);
        $this->assertStringContainsString(route('solar-calculator.calculate'), html_entity_decode($html));
    }

    public function test_author_input_is_escaped_and_unsafe_urls_and_colours_are_dropped(): void
    {
        $this->licenseModules();

        $html = $this->render([
            'title' => '<script>alert(1)</script>',
            'banner_image' => 'javascript:alert(1)',
            'ongrid_image' => 'https://cdn.example.com/ongrid.webp',
            'accent_color' => 'red;background:url(x)',
            'anchor_id' => 'calc"><x',
        ]);

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('javascript:alert', $html);
        $this->assertStringContainsString('https://cdn.example.com/ongrid.webp', $html);
        $this->assertStringNotContainsString('background:url(x)', $html);
        $this->assertStringContainsString('id="calcx"', $html);
    }

    public function test_editor_preview_is_marked_and_blocks_submission(): void
    {
        $this->licenseModules();
        $widget = app(SolarCalculatorWidget::class);

        $html = $widget->render([], 'sc1', true);

        $this->assertStringContainsString('پیش‌نمایش ویرایشگر', $html);
        $this->assertStringContainsString('&quot;preview&quot;:true', $html);
    }

    public function test_assets_are_emitted_once_for_several_widgets(): void
    {
        $this->licenseModules();
        $widget = app(SolarCalculatorWidget::class);

        $html = $widget->render([], 'a') . $widget->render(['anchor_id' => 'second'], 'b');

        $this->assertSame(2, substr_count($html, 'data-sc-calc '));
        $this->assertSame(1, substr_count($html, 'function Calc(root)'));
    }
}

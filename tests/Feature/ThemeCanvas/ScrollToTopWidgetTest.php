<?php

namespace Tests\Feature\ThemeCanvas;

use App\Services\ThemeBuilder\BlockRenderer;
use App\Services\ThemeBuilder\ElementSchemaRegistry;
use App\Services\ThemeCanvas\BlockRegistry;
use App\Services\ThemeCanvas\ThemeCanvasRenderer;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScrollToTopWidgetTest extends TestCase
{
    private function xpathFor(string $html): DOMXPath
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?><body>' . $html . '</body>', LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        return new DOMXPath($dom);
    }

    public function test_scroll_to_top_schema_is_valid_in_element_schema_registry(): void
    {
        $registry = app(ElementSchemaRegistry::class);

        $block = [
            'type' => 'scroll_to_top',
            'id' => 'stt_test_1',
            'props' => [
                'icon_type' => 'svg',
                'icon' => 'arrow-up',
                'show_label' => true,
                'label' => 'برو بالا',
                'orientation' => 'horizontal',
                'position' => 'bottom-right',
                'offset_x' => 30,
                'offset_y' => 30,
                'size' => 'md',
                'border_radius' => 9999,
                'scroll_threshold' => 350,
                'z_index' => 1000,
                'bg_color' => '#2563eb',
                'text_color' => '#ffffff',
                'hover_bg_color' => '#1d4ed8',
                'hover_text_color' => '#ffffff',
                'glow_effect' => true,
                'glow_color' => 'rgba(37, 99, 235, 0.5)',
                'hover_lift' => true,
            ],
        ];

        $isValid = $registry->validate($block);
        $this->assertTrue($isValid, 'ElementSchemaRegistry must validate scroll_to_top without errors: ' . json_encode($registry->getLastErrors()));
    }

    public function test_theme_canvas_renderer_renders_scroll_to_top_correctly(): void
    {
        $renderer = app(ThemeCanvasRenderer::class);
        $html = $renderer->renderBlock([
            'type' => 'scroll_to_top',
            'id' => 'stt_block',
            'props' => [
                'show_label' => true,
                'label' => 'بازگشت به بالا',
                'orientation' => 'vertical',
                'position' => 'bottom-left',
                'size' => 'lg',
                'glow_effect' => true,
            ],
        ]);

        $xpath = $this->xpathFor($html);
        $btn = $xpath->query('//button[contains(concat(" ", normalize-space(@class), " "), " tb-scroll-to-top ")]');
        $this->assertSame(1, $btn->length);

        $button = $btn->item(0);
        $this->assertTrue(str_contains($button->getAttribute('class'), 'tb-scroll-to-top--bottom-left'));
        $this->assertTrue(str_contains($button->getAttribute('class'), 'tb-scroll-to-top--vertical'));
        $this->assertTrue(str_contains($button->getAttribute('class'), 'tb-scroll-to-top--lg'));
        $this->assertTrue(str_contains($button->getAttribute('class'), 'tb-scroll-to-top--glow'));
        $this->assertTrue(str_contains($html, 'data-tb-block-asset="scroll_to_top"'));

        $labelNodes = $xpath->query('//span[contains(concat(" ", normalize-space(@class), " "), " tb-stt-label ")]');
        $this->assertSame(1, $labelNodes->length);
        $this->assertSame('بازگشت به بالا', trim($labelNodes->item(0)->textContent));
    }

    public function test_scroll_to_top_is_registered_in_palette_utility_category_and_builder_elements(): void
    {
        $byCategory = BlockRegistry::byCategory();
        $this->assertArrayHasKey('utility', $byCategory);
        $this->assertArrayHasKey('scroll_to_top', $byCategory['utility']);

        $def = $byCategory['utility']['scroll_to_top'];
        $this->assertStringContainsString('دکمه هدایت به بالا', $def['name']);
        $this->assertNotEmpty($def['description'] ?? null);

        $builderElements = config('builder-elements.elements', []);
        $this->assertArrayHasKey('scroll_to_top', $builderElements);
        $this->assertSame('utility', $builderElements['scroll_to_top']['category']);
        $this->assertStringContainsString('دکمه هدایت به بالا', $builderElements['scroll_to_top']['name']);
    }
}

<?php

namespace Tests\Feature\ThemeCanvas;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BLD-01 (Full Product Commercial Audit, TASK 1/3): `tabs`/`accordion` hit
 * the identical wrong-signature re-dispatch bug the `slider` element had —
 * BlockRenderer::renderBlockUnsafe() computed the correct inner HTML via
 * renderTabs($props['tabs'])/renderAccordion($props['items']), then the
 * generic `render{Type}($props, $inner, $id)` dispatch re-invoked those
 * same single-array-parameter methods with the whole $props array,
 * discarding the real content and rendering one phantom untitled panel.
 */
class TabsAccordionRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        config(['site.owner_site' => true, 'site.platform_site_id' => 601]);
    }

    private function renderer(): \App\Services\ThemeCanvas\ThemeCanvasRenderer
    {
        $renderer = app(\App\Services\ThemeCanvas\ThemeCanvasRenderer::class);
        $renderer->setThemeData(['pages' => []]);
        return $renderer;
    }

    public function test_tabs_renders_real_multiple_tab_titles_and_content(): void
    {
        $html = $this->renderer()->renderBlock([
            'id' => 'tb1',
            'type' => 'tabs',
            'props' => [
                'tabs' => [
                    ['title' => 'ویژگی‌ها', 'blocks' => [['id' => 'b1', 'type' => 'text', 'props' => ['content' => 'محتوای تب یک']]]],
                    ['title' => 'قیمت‌گذاری', 'blocks' => [['id' => 'b2', 'type' => 'text', 'props' => ['content' => 'محتوای تب دو']]]],
                ],
            ],
        ]);

        $this->assertStringContainsString('ویژگی‌ها', $html);
        $this->assertStringContainsString('قیمت‌گذاری', $html);
        $this->assertStringContainsString('محتوای تب یک', $html);
        $this->assertStringContainsString('محتوای تب دو', $html);
        // substr_count('tb-tab-panel') would also match the real nav/
        // switching script's own selector string (TASK 3/3 Phase 5,
        // BLD-16) — count the actual rendered panel DIVs by class attribute.
        $this->assertSame(2, substr_count($html, 'class="tb-tab-panel"'));
    }

    /**
     * TASK 2A (tabs container authoring fix): `tabs` is now a real
     * structural container — each tab is a real `tab_panel` child block
     * (canonical Canvas children), not the legacy `props.tabs[].blocks`
     * shape the tests above exercise. This proves the live customer-site
     * render path (App\Services\ThemeBuilder\BlockRenderer, extended by
     * ThemeCanvasRenderer — the same class layout.blade.php/home.blade.php
     * use for real page rendering) actually renders that canonical shape
     * instead of silently discarding it: a leftover `if ($type === 'tabs')`
     * special case here used to call the shared package's OLD single-array
     * renderTabs(array $tabs) signature and return '' for any block using
     * real tab_panel children, since it never looked at $inner at all.
     */
    public function test_tabs_renders_real_tab_panel_children_canonical_container_content(): void
    {
        $html = $this->renderer()->renderBlock([
            'id' => 'tb4',
            'type' => 'tabs',
            'children' => [
                [
                    'id' => 'tp1',
                    'type' => 'tab_panel',
                    'props' => ['title' => 'ویژگی‌ها'],
                    'children' => [
                        ['id' => 'b1', 'type' => 'text', 'props' => ['content' => 'محتوای تب یک']],
                    ],
                ],
                [
                    'id' => 'tp2',
                    'type' => 'tab_panel',
                    'props' => ['title' => 'قیمت‌گذاری'],
                    'children' => [
                        ['id' => 'b2', 'type' => 'text', 'props' => ['content' => 'محتوای تب دو']],
                    ],
                ],
            ],
        ]);

        $this->assertStringContainsString('ویژگی‌ها', $html);
        $this->assertStringContainsString('قیمت‌گذاری', $html);
        $this->assertStringContainsString('محتوای تب یک', $html);
        $this->assertStringContainsString('محتوای تب دو', $html);
        $this->assertSame(2, substr_count($html, 'class="tb-tab-panel"'));
    }

    public function test_tabs_with_no_tabs_renders_empty_not_a_phantom_panel(): void
    {
        $html = $this->renderer()->renderBlock(['id' => 'tb2', 'type' => 'tabs', 'props' => ['tabs' => []]]);
        $this->assertStringNotContainsString('تب ۱', $html);
        $this->assertStringNotContainsString('tb-tab-panel', $html);
    }

    public function test_accordion_renders_real_multiple_items_with_content(): void
    {
        $html = $this->renderer()->renderBlock([
            'id' => 'ac1',
            'type' => 'accordion',
            'props' => [
                'items' => [
                    ['title' => 'سوال اول', 'blocks' => [['id' => 'b1', 'type' => 'text', 'props' => ['content' => 'پاسخ اول']]]],
                    ['title' => 'سوال دوم', 'blocks' => [['id' => 'b2', 'type' => 'text', 'props' => ['content' => 'پاسخ دوم']]]],
                ],
            ],
        ]);

        $this->assertStringContainsString('سوال اول', $html);
        $this->assertStringContainsString('سوال دوم', $html);
        $this->assertStringContainsString('پاسخ اول', $html);
        $this->assertStringContainsString('پاسخ دوم', $html);
        $this->assertSame(2, substr_count($html, 'tb-accordion-item'));
    }

    public function test_accordion_with_no_items_renders_empty_not_a_phantom_panel(): void
    {
        $html = $this->renderer()->renderBlock(['id' => 'ac2', 'type' => 'accordion', 'props' => ['items' => []]]);
        $this->assertStringNotContainsString('tb-accordion-item', $html);
    }

    public function test_tabs_and_accordion_never_produce_invalid_schema_markers(): void
    {
        $tabsHtml = $this->renderer()->renderBlock([
            'id' => 'tb3', 'type' => 'tabs',
            'props' => ['tabs' => [['title' => 'یک', 'blocks' => []]]],
        ]);
        $accordionHtml = $this->renderer()->renderBlock([
            'id' => 'ac3', 'type' => 'accordion',
            'props' => ['items' => [['title' => 'یک', 'blocks' => []]]],
        ]);

        foreach ([$tabsHtml, $accordionHtml] as $html) {
            $this->assertStringNotContainsString('tb-placeholder-invalid', $html);
            $this->assertStringNotContainsString('[invalid]', $html);
        }
    }
}

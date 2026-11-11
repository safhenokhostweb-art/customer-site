<?php

namespace Tests\Feature\ThemeCanvas;

use App\Services\ThemeCanvas\ThemeCanvasRenderer;
use SaaSPlatform\ThemeRuntime\DefaultHomepage;
use Tests\TestCase;

class DefaultHomepageTest extends TestCase
{
    public function test_portable_document_preserves_properties_and_order_and_renders_both_modes(): void
    {
        $document = DefaultHomepage::document();
        $loaded = json_decode(json_encode($document, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($document, $loaded);
        $blocks = array_merge($loaded['header_blocks'], $loaded['pages']['home']['blocks'], $loaded['footer_blocks']);
        self::assertSame(['header_modern_v1', 'hero', 'features_grid', 'dynamic_product_grid', 'nav_footer'], array_column($blocks, 'type'));
        foreach (['editor', 'public'] as $mode) {
            $html = (new ThemeCanvasRenderer())->setThemeData($loaded)->setMode($mode)->setContext(['editor_mode' => $mode === 'editor'])->renderBlocks($blocks);
            self::assertStringNotContainsString('tb-placeholder', $html);
            self::assertStringContainsString('data-layout-slot="header"', $html);
            self::assertStringContainsString('data-layout-slot="footer"', $html);
            self::assertStringContainsString('محصولات جدید به‌زودی', $html);
            self::assertStringContainsString('خدماتی برای رشد کسب‌وکارهای ایرانی', $html);
            $previous = -1;
            foreach (array_column($blocks, 'id') as $id) {
                $position = strpos($html, 'data-tb-block-id="'.$id.'"');
                self::assertNotFalse($position);
                self::assertGreaterThan($previous, $position);
                $previous = $position;
            }
        }
    }

    public function test_product_grid_renders_authored_items(): void
    {
        $document = DefaultHomepage::document();
        $block = $document['pages']['home']['blocks'][2];
        $block['props']['static_items'] = json_encode([
            ['title' => 'محصول ایرانی', 'price' => '۲۵۰٬۰۰۰ تومان', 'url' => '/shop'],
        ], JSON_UNESCAPED_UNICODE);
        $html = (new ThemeCanvasRenderer())->setMode('public')->renderBlock($block);
        self::assertStringContainsString('data-state="success"', $html);
        self::assertStringContainsString('محصول ایرانی', $html);
        self::assertStringContainsString('href="/shop"', $html);
        self::assertStringNotContainsString('محصولات جدید به‌زودی', strip_tags($html));
    }

    public function test_non_editable_live_chrome_has_slot_markers_without_editor_hooks(): void
    {
        $document = DefaultHomepage::document();
        $html = (new ThemeCanvasRenderer())->setMode('editor')->setContext(['editor_mode' => false])->renderBlocks(
            array_merge($document['header_blocks'], $document['footer_blocks']),
        );
        self::assertSame(1, substr_count($html, 'data-layout-slot="header"'));
        self::assertSame(1, substr_count($html, 'data-layout-slot="footer"'));
        // ADR-076 made data-tb-block-id a public-runtime hook, so it is
        // expected here; only the canvas-editor affordances must be absent.
        self::assertStringNotContainsString('tb-drag-handle', $html);
        self::assertStringNotContainsString('tb-block-badge', $html);
        self::assertStringContainsString('role="banner"', $html);
        self::assertStringContainsString('role="contentinfo"', $html);
    }

    public function test_explicit_slot_render_does_not_add_a_second_wrapper_or_marker(): void
    {
        $document = DefaultHomepage::document();
        $html = app(\App\Services\ThemeCanvas\LayoutSlotBindingService::class)->renderSlot([
            'slot' => 'header', 'block' => $document['header_blocks'][0],
        ], $document);
        self::assertSame(1, substr_count($html, 'data-layout-slot="header"'));
        self::assertStringNotContainsString('<header', $html);
        self::assertStringContainsString('role="banner"', $html);
    }
}

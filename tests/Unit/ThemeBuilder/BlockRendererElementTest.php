<?php

namespace Tests\Unit\ThemeBuilder;

use App\Services\ThemeBuilder\BlockRenderer;
use App\Services\ThemeBuilder\ElementSchemaRegistry;
use Tests\TestCase;

/**
 * Phase 2 — Automated unit tests for builder elements.
 * Covers: default render, empty props, missing optional, extreme values,
 * nested in section, invalid schema → placeholder, render exception → placeholder.
 */
class BlockRendererElementTest extends TestCase
{
    protected BlockRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new BlockRenderer();
        $this->renderer->setSchemaRegistry(new ElementSchemaRegistry());
    }

    public function test_heading_default_render(): void
    {
        $block = ['type' => 'heading', 'id' => 'h1', 'props' => ['level' => 2, 'text' => 'عنوان تست']];
        $html = $this->renderer->renderBlock($block);
        $this->assertStringContainsString('<h2', $html);
        $this->assertStringContainsString('عنوان تست', $html);
        $this->assertStringContainsString('tb-heading', $html);
        $this->assertStringContainsString('id="tb-h1"', $html);
    }

    public function test_heading_empty_props(): void
    {
        $block = ['type' => 'heading', 'id' => 'h2', 'props' => []];
        $html = $this->renderer->renderBlock($block);
        $this->assertStringContainsString('<h2', $html);
        $this->assertStringContainsString('tb-heading', $html);
    }

    public function test_text_default_render(): void
    {
        $block = ['type' => 'text', 'id' => 't1', 'props' => ['content' => '<p>متن تست</p>']];
        $html = $this->renderer->renderBlock($block);
        $this->assertStringContainsString('tb-text', $html);
        $this->assertStringContainsString('متن تست', $html);
    }

    public function test_text_empty_content(): void
    {
        $block = ['type' => 'text', 'id' => 't2', 'props' => ['content' => '']];
        $html = $this->renderer->renderBlock($block);
        $this->assertStringContainsString('tb-text', $html);
    }

    public function test_image_default_render(): void
    {
        $block = ['type' => 'image', 'id' => 'i1', 'props' => ['url' => 'https://example.com/1.jpg', 'alt' => 'تصویر']];
        $html = $this->renderer->renderBlock($block);
        $this->assertStringContainsString('tb-image', $html);
        $this->assertStringContainsString('src="https://example.com/1.jpg"', $html);
        $this->assertStringContainsString('alt="تصویر"', $html);
    }

    public function test_image_empty_url_placeholder_figure(): void
    {
        $block = ['type' => 'image', 'id' => 'i2', 'props' => ['url' => '']];
        $html = $this->renderer->renderBlock($block);
        $this->assertStringContainsString('tb-image-placeholder', $html);
    }

    public function test_button_default_render(): void
    {
        $block = ['type' => 'button', 'id' => 'b1', 'props' => ['text' => 'کلیک', 'url' => '/page', 'style' => 'primary']];
        $html = $this->renderer->renderBlock($block);
        $this->assertStringContainsString('tb-btn', $html);
        $this->assertStringContainsString('کلیک', $html);
        // Phase 2j: a relative internal path like "/page" is a real, safe
        // URL (no scheme to exploit) — was previously incorrectly rejected
        // and replaced with "#" by a URL check that only accepted absolute
        // URLs; fixed, see ThemeRuntimeSecurityTest.
        $this->assertStringContainsString('href="/page"', $html);
        $this->assertStringContainsString('tb-btn-primary', $html);
    }

    public function test_section_nested_render(): void
    {
        $block = [
            'type' => 'section',
            'id' => 's1',
            'props' => ['padding' => 'md', 'background' => 'transparent'],
            'blocks' => [
                ['type' => 'row', 'id' => 'r1', 'blocks' => [
                    ['type' => 'column', 'id' => 'c1', 'props' => ['width' => '1/2'], 'blocks' => [
                        ['type' => 'heading', 'id' => 'h1', 'props' => ['text' => 'داخل ستون']],
                    ]],
                ]],
            ],
        ];
        $html = $this->renderer->renderBlock($block);
        $this->assertStringContainsString('tb-section', $html);
        $this->assertStringContainsString('داخل ستون', $html);
        $this->assertStringContainsString('<h', $html);
    }

    public function test_invalid_schema_returns_placeholder(): void
    {
        $block = ['type' => 'unknown_type', 'id' => 'x1', 'props' => []];
        $html = $this->renderer->renderBlock($block);
        $this->assertStringContainsString('tb-placeholder', $html);
        $this->assertStringContainsString('data-type="unknown_type"', $html);
        $this->assertStringContainsString('invalid', $html);
    }

    public function test_missing_type_returns_placeholder(): void
    {
        $block = ['id' => 'x2', 'props' => []];
        $html = $this->renderer->renderBlock($block);
        $this->assertStringContainsString('tb-placeholder', $html);
    }

    public function test_render_blocks_multiple(): void
    {
        $blocks = [
            ['type' => 'heading', 'id' => 'a', 'props' => ['text' => 'اول']],
            ['type' => 'text', 'id' => 'b', 'props' => ['content' => 'دوم']],
        ];
        $html = $this->renderer->renderBlocks($blocks);
        $this->assertStringContainsString('اول', $html);
        $this->assertStringContainsString('دوم', $html);
    }

    public function test_render_blocks_empty(): void
    {
        $this->assertSame('', $this->renderer->renderBlocks(null));
        $this->assertSame('', $this->renderer->renderBlocks([]));
    }

    public function test_divider_without_styles(): void
    {
        $block = ['type' => 'divider', 'id' => 'd1', 'props' => []];
        $html = $this->renderer->renderBlock($block);
        $this->assertStringContainsString('tb-divider', $html);
        $this->assertStringContainsString('<hr', $html);
    }

    public function test_spacer_with_height(): void
    {
        $block = ['type' => 'spacer', 'id' => 'sp1', 'props' => ['height' => 'lg']];
        $html = $this->renderer->renderBlock($block);
        $this->assertStringContainsString('tb-spacer', $html);
        $this->assertStringContainsString('height:', $html);
    }

    public function test_hero_default_render(): void
    {
        $block = ['type' => 'hero', 'id' => 'hero1', 'props' => ['title' => 'قهرمان', 'subtitle' => 'زیرعنوان']];
        $html = $this->renderer->renderBlock($block);
        $this->assertStringContainsString('tb-hero', $html);
        $this->assertStringContainsString('قهرمان', $html);
        $this->assertStringContainsString('زیرعنوان', $html);
    }
}

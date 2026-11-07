<?php

namespace Tests\Feature\ThemeCanvas;

use App\Services\ThemeBuilder\BlockRenderer;
use Tests\TestCase;

/**
 * Host proof for the saas-platform pin ca4c3d6f (main, release
 * runtime-replace-node-style-tag), carried into this host through the vendor
 * mirror. Fails on the previous pin (0fcade6a).
 *
 * ThemeCanvasRuntime.replaceNode() used to take the new markup's first
 * element as the block. This host puts the block hooks on the header root,
 * and nav_header opens with its own <style>, so live header edits never
 * reached the preview until a reload (the real-browser proof is
 * tests/browser/canvas-header-live-update.spec.js).
 */
class RuntimeReplaceNodePinTest extends TestCase
{
    public function test_the_runtime_finds_the_block_root_past_its_own_style(): void
    {
        $runtime = view('theme-runtime::canvas.runtime')->render();

        $this->assertStringContainsString("nodes.find((el) => !isAsset(el) && el.getAttribute('data-tb-block-id') === blockId)", $runtime, 'the root is the element carrying the block id');
        $this->assertStringNotContainsString('const replacement = template.content.firstElementChild;', $runtime, 'not simply the first element');
        $this->assertStringContainsString("function isStyleAsset(el)", $runtime, 'the block\'s style travels with it');
    }

    public function test_this_host_renders_a_header_whose_markup_opens_with_its_style(): void
    {
        $html = (new BlockRenderer())->setMode('editor')->setContext(['editor_mode' => true])
            ->renderBlock(['type' => 'nav_header', 'id' => 'h1', 'props' => ['logo_text' => 'برند']]);

        $this->assertStringStartsWith('<style', ltrim($html), 'the case the runtime must handle');
        $this->assertMatchesRegularExpression('/<div[^>]*\sid="tb-h1"[^>]*\sdata-tb-block-id="h1"/', $html, 'the hook is on the header root');
    }
}

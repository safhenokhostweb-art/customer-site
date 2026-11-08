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

    /**
     * saas ADR-082 step 2: a default header no longer opens with a <style>
     * (its layout is the shared header sheet). A header with its own values
     * (HeaderStyleVars, step 3) opens with `<style data-tb-hdr>` again, so
     * the runtime's style-aware swap above stays the contract.
     */
    public function test_this_host_puts_the_hook_on_the_header_root_with_or_without_a_leading_style(): void
    {
        $html = (new BlockRenderer())->setMode('editor')->setContext(['editor_mode' => true])
            ->renderBlock(['type' => 'nav_header', 'id' => 'h1', 'props' => ['logo_text' => 'برند']]);

        $this->assertMatchesRegularExpression('/^<div[^>]*\sid="tb-h1"[^>]*\sdata-tb-block-id="h1"/', ltrim($html), 'a default header opens with its root, which carries the hook');
        $this->assertStringNotContainsString('<style', $html);

        $scoped = \SaaSPlatform\ThemeRuntime\Widget\HeaderStyleVars::style('tb-h1', ['min-h' => 80]);
        $this->assertStringStartsWith('<style data-tb-hdr="tb-h1">#tb-h1{--tb-hdr-min-h:80px}</style>', $scoped, 'the leading style a customised header will carry');
    }
}

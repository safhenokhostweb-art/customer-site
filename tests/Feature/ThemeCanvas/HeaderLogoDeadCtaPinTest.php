<?php

namespace Tests\Feature\ThemeCanvas;

use App\Services\ThemeBuilder\BlockRenderer;
use Tests\TestCase;

/**
 * Host proof for saas-platform fe7a608b: the two storefront defects the
 * Sentinel UI verifier reported at 360 px reach a live tenant page fixed
 * through the canonical package. Fails on the previous pin (93e24e7d).
 *
 * - An image logo with a long brand name no longer widens the page: the logo
 *   shrinks to its row and the name wraps (it measured 25 px of overflow).
 * - A header CTA without a destination is left out of the public page and
 *   shown, marked, in the editor (Sentinel DEAD_LINK on header_modern_v1).
 */
class HeaderLogoDeadCtaPinTest extends TestCase
{
    public function test_the_image_logo_shrinks_so_a_long_brand_name_cannot_widen_the_page(): void
    {
        $css = (string) file_get_contents(\SaaSPlatform\ThemeRuntime\Providers\ThemeRuntimeServiceProvider::cssBlocksPath());
        $this->assertStringContainsString('.tb-nav-header-logo--image { display: inline-flex; align-items: center; gap: 0.6rem; flex: 0 1 auto; min-width: 0; max-width: 100%; }', $css);
        $this->assertStringContainsString('.tb-nav-header-logo-text { line-height: 1.2; min-width: 0; overflow-wrap: anywhere; }', $css);
        $this->assertDoesNotMatchRegularExpression('/\.tb-nav-header-logo--image \{[^}]*flex: none/', $css);

        $partial = (string) file_get_contents(resource_path('views/partials/theme-canvas-style-contract.blade.php'));
        $this->assertStringContainsString("@include('theme-runtime::css.blocks')", $partial, 'the storefront style contract ships the shared sheet, rule included');
    }

    public function test_a_header_cta_without_a_destination_stays_off_the_storefront_but_shows_in_the_editor(): void
    {
        $block = ['type' => 'header_modern_v1', 'id' => 'h1', 'props' => ['ctaText' => 'شروع', 'ctaUrl' => '#']];

        $site = (new BlockRenderer())->setMode('public')->renderBlock($block);
        $this->assertStringNotContainsString('tb-header-modern__cta"', $site);
        $this->assertStringNotContainsString('href="#"', $site);

        $editor = (new BlockRenderer())->setContext(['editor_mode' => true])->renderBlock($block);
        $this->assertStringContainsString('class="tb-header-modern__cta" href="#" data-tb-dead-link', $editor);

        $live = (new BlockRenderer())->setMode('public')->renderBlock(['type' => 'nav_header', 'id' => 'h2', 'props' => ['cta_text' => 'شروع', 'cta_url' => '/contact']]);
        $this->assertStringContainsString('<a href="/contact" class="tb-btn tb-btn-primary tb-nav-header-cta">شروع</a>', $live);
    }
}

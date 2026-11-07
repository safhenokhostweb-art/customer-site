<?php

namespace Tests\Feature\ThemeCanvas;

use App\Services\ThemeBuilder\BlockRenderer;
use SaaSPlatform\ThemeRuntime\Providers\ThemeRuntimeServiceProvider;
use Tests\TestCase;

/**
 * Host proof for the saas-platform pin b563dffd (main), WU-SLIDER-DOT-TAP-TARGET:
 * the storefront's slider dots reach this host as 24x24 px tap targets
 * (WCAG 2.2 SC 2.5.8, Design HUD TAP_TARGET_SMALL) through the vendor mirror
 * of the shared sheets. Fails on the previous pin (e6295b32), where they were
 * 9x9 px buttons 0.4rem apart.
 *
 * The painted dot stays 0.55rem (background-clip: content-box), and the dot
 * colours use background-color, because the background shorthand with
 * !important would reset the clip and paint the whole 24 px box.
 */
class SliderDotTapTargetPinTest extends TestCase
{
    public function test_the_shared_sheet_makes_each_dot_a_24px_button_around_the_painted_dot(): void
    {
        $blocks = (string) file_get_contents(ThemeRuntimeServiceProvider::cssBlocksPath());

        $this->assertStringContainsString('.tb-carousel-dots, .tb-carousel-dot { --tb-dot-pad: max(0px, calc((24px - 0.55rem) / 2)); }', $blocks);
        $this->assertStringContainsString('.tb-carousel-dots { display: flex; justify-content: center; gap: 0; margin-top: calc(0.85rem - var(--tb-dot-pad)); margin-bottom: calc(-1 * var(--tb-dot-pad)); }', $blocks, 'buttons abut, and the margins give the padding back');
        $this->assertStringContainsString('.tb-carousel-dot { box-sizing: border-box; width: calc(0.55rem + 2 * var(--tb-dot-pad)); height: calc(0.55rem + 2 * var(--tb-dot-pad)); padding: var(--tb-dot-pad);', $blocks);
        $this->assertStringContainsString('background-clip: content-box;', $blocks);
        $this->assertStringContainsString('.tb-carousel-dot.is-active { background-color: var(--tb-primary, #4f46e5); }', $blocks);
    }

    public function test_the_contract_sheet_keeps_the_full_width_dots_painted_and_placed_as_before(): void
    {
        $contract = (string) file_get_contents(ThemeRuntimeServiceProvider::cssContractPath());

        $this->assertStringContainsString('.tb-slider-full .tb-carousel-dots { position: absolute; bottom: calc(1.35rem - var(--tb-dot-pad, 0px));', $contract);
        $this->assertStringContainsString('.tb-slider-full .tb-carousel-dot[aria-current="true"] { width: calc(2.4rem + 2 * var(--tb-dot-pad, 0px)) !important;', $contract);
        $this->assertStringContainsString('.tb-carousel-dot:hover { background-color: var(--tb-primary, #4f46e5) !important; opacity: .7; }', $contract);
        foreach ([$contract, (string) file_get_contents(ThemeRuntimeServiceProvider::cssBlocksPath())] as $css) {
            $this->assertDoesNotMatchRegularExpression('/\.tb-carousel-dot[^{,]*\{[^}]*(?<![-\w])background\s*:(?!\s*var\(--tb-border-strong)/', $css, 'no dot rule uses the background shorthand');
        }
    }

    public function test_the_storefront_ships_both_sheets_and_the_dot_buttons(): void
    {
        $partial = (string) file_get_contents(resource_path('views/partials/theme-canvas-style-contract.blade.php'));
        $this->assertStringContainsString("@include('theme-runtime::css.contract')", $partial);
        $this->assertStringContainsString("@include('theme-runtime::css.blocks')", $partial);

        $html = (new BlockRenderer())->setMode('public')->renderBlock(['type' => 'slider', 'id' => 's1', 'props' => [
            'slides' => [['image' => '/a.png', 'title' => 'یک'], ['image' => '/b.png', 'title' => 'دو']],
            'autoplay' => false,
        ]]);
        $this->assertStringContainsString('<button type="button" class="tb-carousel-dot is-active" aria-label="رفتن به اسلاید ۱" aria-current="true"></button>', $html);
        $this->assertStringContainsString('<button type="button" class="tb-carousel-dot" aria-label="رفتن به اسلاید ۲" aria-current="false"></button>', $html);
    }
}

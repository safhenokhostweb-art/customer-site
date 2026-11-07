<?php

namespace Tests\Feature\ThemeCanvas;

use SaaSPlatform\ThemeRuntime\Providers\ThemeRuntimeServiceProvider;
use Tests\TestCase;

/**
 * Host proof for the saas-platform pin of WU-SLIDER-IN-COLUMNS-HEIGHT: a
 * full-width slider in a `columns` / `layout-grid` cell follows its own
 * aspect_ratio on the storefront, so beside a 16:9 module:motion with the
 * same ratio both stand equally tall (344px each at 1280px, where the slider
 * was 614px before). The fix reaches this host only through the vendor mirror
 * of the shared contract sheet; this fails on the previous pin (b563dffd),
 * where the viewport-sized hero min-height applied inside a cell too.
 *
 * Standalone sliders keep the hero min-height, and the b563dffd dot rules
 * (SliderDotTapTargetPinTest) stay as they are.
 */
class SliderInColumnsHeightPinTest extends TestCase
{
    private function contract(): string
    {
        return (string) file_get_contents(ThemeRuntimeServiceProvider::cssContractPath());
    }

    public function test_a_slider_in_a_cell_drops_the_viewport_sized_min_height(): void
    {
        $this->assertStringContainsString(
            ".tb-columns .tb-slider-full .tb-slide,\n.tb-layout-grid .tb-slider-full .tb-slide { min-height: 0; }",
            str_replace("\r\n", "\n", $this->contract())
        );
    }

    public function test_a_narrow_cell_scales_the_caption_with_the_cell_and_hides_thumbnails(): void
    {
        $contract = $this->contract();

        $this->assertMatchesRegularExpression(
            '/@container tb-slot \(max-width: 767\.98px\) \{\s*\.tb-slider-full \.tb-slide-caption \{ width: 100%; margin: 0; padding: clamp\(1rem, 5cqi, 2rem\) 3\.25rem 5\.75rem !important; align-self: flex-end; \}/',
            $contract
        );
        $this->assertStringContainsString('.tb-slider-full .tb-slide-title { font-size: clamp(1.1rem, 5cqi, 2rem) !important; }', $contract);
        $this->assertStringContainsString('.tb-slider-full .tb-carousel-thumbs { display: none; }', $contract);
    }

    public function test_a_standalone_slider_keeps_its_hero_height(): void
    {
        $contract = $this->contract();

        $this->assertStringContainsString('.tb-slider-full .tb-slide { min-height: clamp(420px,48vw,640px); }', $contract);
        $this->assertStringContainsString('.tb-slider-full .tb-slide { min-height: 540px; }', $contract);
    }
}

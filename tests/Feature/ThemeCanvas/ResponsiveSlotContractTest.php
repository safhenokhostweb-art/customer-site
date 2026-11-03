<?php

namespace Tests\Feature\ThemeCanvas;

use App\Services\ThemeCanvas\ThemeCanvasRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Host proof for saas-platform WU-C (commercial tokens) and WU-D (breakpoint
 * scale, tb-slot container queries, boxed slider controls; saas-platform
 * ADR-071), released together as saas-platform 9eb6acfb. customer-site
 * renders through the canonical package, so these assert what a live tenant
 * page actually receives: the renderer markup and the two shared sheets the
 * style-contract partial includes.
 */
class ResponsiveSlotContractTest extends TestCase
{
    use RefreshDatabase;

    private const ON_SCALE = [
        'max-width: 479.98px', 'max-width: 767.98px', 'max-width: 1023.98px', 'max-width: 1279.98px',
        'min-width: 480px', 'min-width: 768px', 'min-width: 1024px', 'min-width: 1280px',
    ];

    private function renderer(): ThemeCanvasRenderer
    {
        $renderer = app(ThemeCanvasRenderer::class);
        $renderer->setThemeData(['pages' => []]);

        return $renderer;
    }

    private function sheets(): string
    {
        return view('theme-runtime::css.contract')->render() . "\n" . view('theme-runtime::css.blocks')->render();
    }

    public function test_the_style_contract_partial_ships_both_shared_sheets(): void
    {
        $partial = (string) file_get_contents(resource_path('views/partials/theme-canvas-style-contract.blade.php'));

        $this->assertStringContainsString("@include('theme-runtime::css.contract')", $partial);
        $this->assertStringContainsString("@include('theme-runtime::css.blocks')", $partial);
    }

    public function test_grid_layout_block_children_become_tb_slot_containers(): void
    {
        $html = $this->renderer()->renderBlock([
            'id' => 'g1', 'type' => 'grid', 'props' => ['columns' => 2],
            'children' => [
                ['id' => 'h1', 'type' => 'heading', 'props' => ['text' => 'ستون یک']],
                ['id' => 'h2', 'type' => 'heading', 'props' => ['text' => 'ستون دو']],
            ],
        ]);

        $this->assertStringContainsString('tb-layout-grid', $html);
        $this->assertStringContainsString('ستون یک', $html);
        $this->assertStringNotContainsString('tb-placeholder-invalid', $html);

        $css = $this->sheets();
        $this->assertMatchesRegularExpression('/\.tb-layout-grid > :not\(style\):not\(:has\([^{]*\{ container: tb-slot \/ inline-size; \}/', $css);
        $this->assertStringContainsString('@container tb-slot (max-width: 767.98px)', $css);
        $this->assertStringContainsString('@container tb-slot (max-width: 479.98px)', $css);
    }

    public function test_boxed_slider_ships_styled_controls_and_slot_aware_item_counts(): void
    {
        $html = $this->renderer()->renderBlock([
            'id' => 'sl1', 'type' => 'slider',
            'props' => ['boxed' => true, 'slides' => [
                ['image' => 'https://example.test/a.webp', 'title' => 'اسلاید اول'],
                ['image' => 'https://example.test/b.webp', 'title' => 'اسلاید دوم'],
            ]],
        ]);

        $this->assertStringContainsString('tb-slider-boxed', $html);
        $this->assertStringContainsString('class="tb-carousel-thumbs"', $html);
        $this->assertStringContainsString('@media (max-width:479.98px)', $html);
        $this->assertStringContainsString('@container tb-slot (max-width:479.98px)', $html);
        $this->assertStringNotContainsString('max-width:640px', $html);

        $css = $this->sheets();
        foreach (['.tb-slider-boxed .tb-carousel-status {', '.tb-slider-boxed .tb-carousel-pause {', '.tb-slider-boxed .tb-carousel-thumb {'] as $rule) {
            $this->assertStringContainsString($rule, $css);
        }
    }

    public function test_shared_sheets_use_only_the_breakpoint_scale_plus_the_pinned_legacy_rules(): void
    {
        preg_match_all('/@(?:media|container)\b[^{]*\b(min|max)-width\s*:\s*([0-9.]+)(px|rem|em)\b/', $this->sheets(), $m, PREG_SET_ORDER);
        $widths = array_map(fn (array $one) => "{$one[1]}-width: {$one[2]}{$one[3]}", $m);

        $this->assertNotSame([], $widths);
        $this->assertSame([], array_values(array_filter($widths, fn (string $w) => !in_array($w, self::ON_SCALE, true) && $w !== 'max-width: 768px')));
        // saas-platform ADR-071: the three legacy contract rules stay (owner decision).
        $this->assertSame(3, count(array_keys($widths, 'max-width: 768px', true)));
    }

    public function test_commercial_tokens_reach_the_host_sheet_with_legacy_fallbacks(): void
    {
        $css = $this->sheets();

        $this->assertStringContainsString('var(--tb-star, #f59e0b)', $css);
        $this->assertStringContainsString('var(--tb-border-strong, #d1d5db)', $css);
        $this->assertStringContainsString('var(--tb-backdrop, rgba(15, 23, 42, 0.55))', $css);
    }
}

<?php

namespace Tests\Unit\MotionGraphics;

use App\Services\MotionGraphics\MotionRuntimeRenderer;
use Tests\TestCase;

class MotionRuntimeRendererTest extends TestCase
{
    private function asset(array $overrides = []): array
    {
        return array_merge([
            'asset_id' => 'motion-1',
            'published_version' => 1,
            'checksum' => str_repeat('a', 64),
            'asset_type' => 'html',
            'full_reference' => 'https://platform.test/assets/motion-1/v1/output.html',
        ], $overrides);
    }

    public function test_html_asset_renders_a_sandboxed_iframe(): void
    {
        $html = (new MotionRuntimeRenderer())->render($this->asset(['asset_type' => 'html']));

        $this->assertStringContainsString('<iframe', $html);
        $this->assertStringContainsString('sandbox="allow-scripts"', $html);
        $this->assertStringContainsString('referrerpolicy="no-referrer"', $html);
    }

    public function test_web_component_asset_renders_an_sri_checked_module_script(): void
    {
        $html = (new MotionRuntimeRenderer())->render($this->asset(['asset_type' => 'web-component']));

        $this->assertStringContainsString('<script type="module"', $html);
        $this->assertStringContainsString('integrity="sha256-', $html);
        $this->assertStringContainsString('crossorigin="anonymous"', $html);
    }

    public function test_svg_asset_renders_an_img_tag_not_inline_svg(): void
    {
        $html = (new MotionRuntimeRenderer())->render($this->asset(['asset_type' => 'svg']));

        $this->assertStringContainsString('<img', $html);
        $this->assertStringNotContainsString('<svg', $html);
        $this->assertStringNotContainsString('<script', $html);
    }

    public function test_lottie_asset_renders_nothing_no_player_wired_yet(): void
    {
        $html = (new MotionRuntimeRenderer())->render($this->asset(['asset_type' => 'lottie']));

        $this->assertNull($html);
    }

    public function test_unknown_asset_type_renders_nothing(): void
    {
        $html = (new MotionRuntimeRenderer())->render($this->asset(['asset_type' => 'totally-unknown']));

        $this->assertNull($html);
    }

    public function test_a_missing_reference_renders_nothing_even_for_a_known_type(): void
    {
        $html = (new MotionRuntimeRenderer())->render($this->asset(['asset_type' => 'html', 'full_reference' => null]));

        $this->assertNull($html);
    }

    public function test_asset_id_is_escaped_in_output(): void
    {
        $html = (new MotionRuntimeRenderer())->render($this->asset(['asset_type' => 'html', 'asset_id' => '"><script>alert(1)</script>']));

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
    }
}

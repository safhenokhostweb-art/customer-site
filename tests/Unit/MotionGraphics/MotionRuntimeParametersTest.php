<?php

namespace Tests\Unit\MotionGraphics;

use App\Services\MotionGraphics\MotionRuntimeParameters;
use App\Services\MotionGraphics\MotionRuntimeRenderer;
use App\Services\ThemeBuilder\ElementSchemaRegistry;
use Tests\TestCase;

/**
 * saas-platform ADR-079 — customer-site half of the motion runtime
 * parameter contract, and nested module:motion validation.
 */
class MotionRuntimeParametersTest extends TestCase
{
    private function definitions(): array
    {
        return MotionRuntimeParameters::normalizeDefinitions([
            ['key' => 'window_title', 'type' => 'text', 'label' => 'عنوان', 'default' => 'داشبورد', 'max' => 10],
            ['key' => 'primary', 'type' => 'color', 'label' => 'رنگ', 'default' => '#2563eb'],
            ['key' => 'share', 'type' => 'number', 'label' => 'سهم', 'min' => 0, 'max' => 100, 'default' => 36],
            ['key' => 'loop', 'type' => 'boolean', 'label' => 'لوپ', 'default' => true],
            ['key' => 'speed', 'type' => 'select', 'label' => 'سرعت', 'options' => ['slow' => 'آرام', 'normal' => 'عادی'], 'default' => 'normal'],
            ['key' => 'Bad Key', 'type' => 'text', 'label' => 'x', 'default' => 'x'],
            ['key' => 'evil', 'type' => 'script', 'label' => 'x', 'default' => 'x'],
        ]);
    }

    public function test_malformed_definitions_are_dropped(): void
    {
        $this->assertSame(['window_title', 'primary', 'share', 'loop', 'speed'], array_column($this->definitions(), 'key'));
        $this->assertSame([], MotionRuntimeParameters::normalizeDefinitions('not-an-array'));
    }

    public function test_only_declared_valid_non_default_values_are_forwarded_sorted(): void
    {
        $query = MotionRuntimeParameters::query($this->definitions(), [
            'window_title' => "  پنل\u{202E} مدیریت شرکت آریا  ",
            'primary' => '#0F766E',
            'share' => 36,              // default → dropped
            'loop' => false,
            'speed' => 'warp',          // not an option → dropped
            'unknown' => 'x',
        ]);

        $this->assertSame(['loop' => '0', 'primary' => '#0f766e', 'window_title' => 'پنل مدیریت'], $query);
    }

    public function test_adr080_sections_scheme_and_large_parameter_sets_survive(): void
    {
        $declared = [
            ['key' => 'scheme', 'type' => 'select', 'group' => 'style', 'label' => 'حالت رنگی', 'options' => ['auto' => 'خودکار', 'light' => 'روشن', 'dark' => 'تیره'], 'default' => 'auto'],
            ['key' => 'show_kpis', 'type' => 'boolean', 'role' => 'show', 'group' => 'sections', 'label' => 'کارت‌های شاخص', 'default' => true],
        ];
        for ($i = 1; $i <= 60; $i++) {
            $declared[] = ['key' => 'text_'.$i, 'type' => 'text', 'label' => 'متن '.$i, 'default' => 'متن', 'max' => 10];
        }
        $definitions = MotionRuntimeParameters::normalizeDefinitions($declared);

        $this->assertCount(62, $definitions, 'a v3 motion declares more than ADR-079\'s 40 parameters');
        $this->assertSame('sections', $definitions[1]['group'], 'the inspector groups show/hide switches under «بخش‌ها»');
        $this->assertSame(
            ['scheme' => 'dark', 'show_kpis' => '0', 'text_60' => 'آخر'],
            MotionRuntimeParameters::query($definitions, ['show_kpis' => false, 'scheme' => 'dark', 'text_60' => 'آخر'])
        );
    }

    public function test_json_encoded_params_are_accepted(): void
    {
        $this->assertSame(['share' => '55'], MotionRuntimeParameters::query($this->definitions(), json_encode(['share' => 55])));
        $this->assertSame([], MotionRuntimeParameters::query($this->definitions(), '{broken'));
    }

    public function test_the_renderer_appends_the_validated_query_to_the_svg_url(): void
    {
        $asset = [
            'asset_id' => 'motion-1', 'published_version' => 2, 'checksum' => str_repeat('a', 64), 'asset_type' => 'svg',
            'name' => 'موشن', 'full_reference' => 'https://platform.test/motion/hero-motion/v2/animation.svg',
            'parameters' => [['key' => 'window_title', 'type' => 'text', 'label' => 'عنوان', 'default' => 'داشبورد']],
        ];

        $html = (new MotionRuntimeRenderer())->render($asset, ['window_title' => 'آریا', 'injected' => '"><script>']);

        $this->assertStringContainsString('animation.svg?window_title=%D8%A2%D8%B1%DB%8C%D8%A7', $html);
        $this->assertStringNotContainsString('injected', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringContainsString('src="https://platform.test/motion/hero-motion/v2/animation.svg"', (new MotionRuntimeRenderer())->render($asset));
    }

    public function test_module_motion_nested_in_a_container_validates(): void
    {
        $registry = new ElementSchemaRegistry();
        $motion = ['type' => 'module:motion', 'props' => [
            'motion_asset_id' => 'motion-1', 'placement' => 'hero', 'enabled' => true, 'fallback_image' => '',
            'motion_params' => ['window_title' => 'آریا'],
        ]];

        foreach (['flex_container', 'section', 'container', 'grid', 'columns'] as $container) {
            $this->assertTrue($registry->validate(['type' => $container, 'props' => [], 'blocks' => [$motion]]), $container.': '.implode(' ', $registry->getLastErrors()));
        }

        $broken = $motion;
        $broken['props']['motion_params'] = 'not json';
        $this->assertFalse($registry->validate(['type' => 'flex_container', 'props' => [], 'blocks' => [$broken]]));
        $this->assertStringContainsString('motion_params', implode(' ', $registry->getLastErrors()));
        $this->assertStringNotContainsString('Unknown element type', implode(' ', $registry->getLastErrors()));
    }
}

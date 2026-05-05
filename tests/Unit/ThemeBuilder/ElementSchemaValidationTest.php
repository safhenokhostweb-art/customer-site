<?php

namespace Tests\Unit\ThemeBuilder;

use App\Services\ThemeBuilder\ElementSchemaRegistry;
use Tests\TestCase;

/**
 * Phase 1 — Element contract validation.
 * Ensures each builder element type validates against its schema.
 */
class ElementSchemaValidationTest extends TestCase
{
    protected ElementSchemaRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new ElementSchemaRegistry();
    }

    public function test_heading_valid_default(): void
    {
        $block = ['type' => 'heading', 'id' => 'h1', 'props' => ['level' => 2, 'text' => 'عنوان']];
        $this->assertTrue($this->registry->validate($block));
    }

    public function test_heading_empty_props(): void
    {
        $block = ['type' => 'heading', 'id' => 'h1', 'props' => []];
        $this->assertTrue($this->registry->validate($block));
    }

    public function test_heading_missing_optional_values(): void
    {
        $block = ['type' => 'heading', 'props' => []];
        $this->assertTrue($this->registry->validate($block));
    }

    public function test_heading_extreme_long_text(): void
    {
        $long = str_repeat('a', 70000);
        config(['builder-element-schemas.limits.string_prop_max_length' => 65535]);
        $block = ['type' => 'heading', 'props' => ['text' => $long]];
        $this->assertFalse($this->registry->validate($block));
        $this->assertNotEmpty($this->registry->getLastErrors());
    }

    public function test_text_valid(): void
    {
        $block = ['type' => 'text', 'props' => ['content' => 'متن']];
        $this->assertTrue($this->registry->validate($block));
    }

    public function test_text_empty_props(): void
    {
        $block = ['type' => 'text', 'props' => []];
        $this->assertTrue($this->registry->validate($block));
    }

    public function test_image_valid(): void
    {
        $block = ['type' => 'image', 'props' => ['url' => 'https://example.com/img.jpg', 'alt' => 'توضیح']];
        $this->assertTrue($this->registry->validate($block));
    }

    public function test_image_empty_url(): void
    {
        $block = ['type' => 'image', 'props' => ['url' => '', 'alt' => '']];
        $this->assertTrue($this->registry->validate($block));
    }

    public function test_image_huge_url_fails(): void
    {
        config(['builder-element-schemas.limits.url_max_length' => 2048]);
        $block = ['type' => 'image', 'props' => ['url' => str_repeat('a', 3000)]];
        $this->assertFalse($this->registry->validate($block));
    }

    public function test_button_valid(): void
    {
        $block = ['type' => 'button', 'props' => ['text' => 'دکمه', 'url' => '#', 'style' => 'primary']];
        $this->assertTrue($this->registry->validate($block));
    }

    public function test_button_empty_props(): void
    {
        $block = ['type' => 'button', 'props' => []];
        $this->assertTrue($this->registry->validate($block));
    }

    public function test_section_valid_with_children(): void
    {
        $block = [
            'type' => 'section',
            'id' => 's1',
            'props' => ['padding' => 'md', 'background' => 'transparent'],
            'blocks' => [
                ['type' => 'row', 'blocks' => [
                    ['type' => 'column', 'props' => ['width' => '1/2'], 'blocks' => [
                        ['type' => 'heading', 'props' => ['text' => 'عنوان']],
                    ]],
                ]],
            ],
        ];
        $this->assertTrue($this->registry->validate($block));
    }

    public function test_section_without_styles(): void
    {
        $block = ['type' => 'section', 'props' => [], 'blocks' => []];
        $this->assertTrue($this->registry->validate($block));
    }

    public function test_unknown_type_fails(): void
    {
        $block = ['type' => 'unknown_widget', 'props' => []];
        $this->assertFalse($this->registry->validate($block));
        $this->assertStringContainsString('Unknown element type', implode(' ', $this->registry->getLastErrors()));
    }

    public function test_missing_type_fails(): void
    {
        $block = ['id' => 'x', 'props' => []];
        $this->assertFalse($this->registry->validate($block));
    }

    public function test_props_must_be_array(): void
    {
        $block = ['type' => 'heading', 'props' => 'not-array'];
        $this->assertFalse($this->registry->validate($block));
    }

    public function test_style_must_be_array_if_present(): void
    {
        $block = ['type' => 'heading', 'props' => [], 'style' => 'invalid'];
        $this->assertFalse($this->registry->validate($block));
    }

    public function test_children_must_be_array(): void
    {
        $block = ['type' => 'section', 'props' => [], 'blocks' => 'not-array'];
        $this->assertFalse($this->registry->validate($block));
    }

    public function test_registry_has_all_expected_types(): void
    {
        $types = $this->registry->getRegisteredTypes();
        $this->assertContains('heading', $types);
        $this->assertContains('text', $types);
        $this->assertContains('image', $types);
        $this->assertContains('button', $types);
        $this->assertContains('section', $types);
        $this->assertContains('row', $types);
        $this->assertContains('column', $types);
    }

    public function test_deeply_nested_children_valid(): void
    {
        $block = ['type' => 'section', 'props' => [], 'blocks' => [
            ['type' => 'row', 'blocks' => [
                ['type' => 'column', 'blocks' => [
                    ['type' => 'section', 'blocks' => [
                        ['type' => 'row', 'blocks' => [
                            ['type' => 'column', 'blocks' => [
                                ['type' => 'heading', 'props' => ['text' => 'عمیق']],
                            ]],
                        ]],
                    ]],
                ]],
            ]],
        ]];
        $this->assertTrue($this->registry->validate($block));
    }
}

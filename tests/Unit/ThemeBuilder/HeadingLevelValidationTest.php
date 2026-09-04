<?php

namespace Tests\Unit\ThemeBuilder;

use App\Builder\Definition\ThemeBuilderElementDefinition;
use App\Builder\Definition\ThemeBuilderExtensionBlock;
use App\Builder\Definition\ThemeBuilderExtensionDefinition;
use App\Builder\ElementRegistry;
use App\Builder\Extension\ThemeBuilderExtensionRegistrar;
use App\Services\ThemeBuilder\ElementSchemaRegistry;
use App\Services\ThemeCanvas\Extension\ExtensionBlockTypeRegistry;
use App\Services\ThemeCanvas\Extension\ExtensionProductIdentity;
use App\Services\ThemeCanvas\ThemeCanvasRenderer;
use Tests\TestCase;
use Tests\Unit\Builder\Extension\Fixtures\DemoCardElement;
use Tests\Unit\Builder\Extension\Fixtures\DemoContainerElement;

/**
 * Phase 3F-D — proves the ElementSchemaRegistry::assertType() validation-
 * boundary fix for `heading.level` (and every other select-backed
 * integer-typed prop): neither Production Builder's nor Canvas's `<select>`
 * control uses Alpine's `.number` x-model modifier, so a real, unmodified,
 * user-chosen value arrives here as a numeric STRING ('2') — diverging
 * from every existing hand-authored fixture/preset/kit in this codebase,
 * which stores a real int. The fix accepts a genuine integer-string
 * representation as satisfying the 'integer' type, without loosening
 * rejection of anything else (non-numeric strings, decimals, arrays) and
 * without changing the canonical (integer) domain type or any persisted
 * data shape.
 */
class HeadingLevelValidationTest extends TestCase
{
    protected function tearDown(): void
    {
        ExtensionBlockTypeRegistry::reset();
        ElementRegistry::clearCache();
        parent::tearDown();
    }

    private function registerDemoSuite(): void
    {
        $package = ThemeBuilderExtensionDefinition::make(
            ExtensionProductIdentity::fromParts('acme', 'demo-suite'),
            '1.0.0',
            ThemeBuilderExtensionDefinition::SDK_CONTRACT_V1,
            [
                ThemeBuilderExtensionBlock::make('ext:acme:demo_card', ThemeBuilderElementDefinition::fromElementClass(DemoCardElement::class), DemoCardElement::class),
                ThemeBuilderExtensionBlock::make('ext:acme:demo_container', ThemeBuilderElementDefinition::fromElementClass(DemoContainerElement::class), DemoContainerElement::class),
            ],
        );
        ThemeBuilderExtensionRegistrar::register($package);
    }

    // ── A. the browser-shaped (string) value is now accepted ──────────────

    public function test_browser_select_shaped_string_level_is_accepted(): void
    {
        $registry = new ElementSchemaRegistry();
        $block = ['type' => 'heading', 'props' => ['text' => 'X', 'level' => '2']];

        $this->assertTrue($registry->validate($block), implode('; ', $registry->getLastErrors()));
    }

    // ── B. historical/persisted real-int shape remains accepted ───────────

    public function test_historical_integer_level_still_accepted(): void
    {
        $registry = new ElementSchemaRegistry();
        $block = ['type' => 'heading', 'props' => ['text' => 'X', 'level' => 2]];

        $this->assertTrue($registry->validate($block));
    }

    /**
     * The full domain (1-6, matching HeadingElement::getSchema()'s own
     * enum) is validation-accepted for all string-shaped representatives —
     * Canvas's own <select> currently only exposes options 1-4 in its UI
     * (a separate, pre-existing UI-completeness gap, not fixed here), but
     * the validation/render layer's domain is not limited to what any one
     * UI happens to expose.
     */
    public function test_full_domain_string_levels_accepted(): void
    {
        $registry = new ElementSchemaRegistry();
        foreach (['1', '2', '3', '4', '5', '6'] as $level) {
            $block = ['type' => 'heading', 'props' => ['text' => 'X', 'level' => $level]];
            $this->assertTrue($registry->validate($block), "level={$level} should validate");
        }
    }

    // ── C. genuinely invalid values still fail closed ──────────────────────

    public function test_non_numeric_string_level_is_rejected(): void
    {
        $registry = new ElementSchemaRegistry();
        $block = ['type' => 'heading', 'props' => ['text' => 'X', 'level' => 'abc']];

        $this->assertFalse($registry->validate($block));
    }

    public function test_decimal_string_level_is_rejected(): void
    {
        $registry = new ElementSchemaRegistry();
        $block = ['type' => 'heading', 'props' => ['text' => 'X', 'level' => '2.5']];

        $this->assertFalse($registry->validate($block));
    }

    public function test_array_level_is_rejected(): void
    {
        $registry = new ElementSchemaRegistry();
        $block = ['type' => 'heading', 'props' => ['text' => 'X', 'level' => ['x']]];

        $this->assertFalse($registry->validate($block));
    }

    // ── D. a valid nested heading no longer drops its parent subtree ──────

    public function test_nested_string_level_heading_no_longer_invalidates_parent_section(): void
    {
        $registry = new ElementSchemaRegistry();
        $block = [
            'type' => 'section',
            'props' => [],
            'blocks' => [
                ['type' => 'heading', 'props' => ['text' => 'Nested', 'level' => '3']],
            ],
        ];

        $this->assertTrue($registry->validate($block), implode('; ', $registry->getLastErrors()));
    }

    public function test_unrelated_genuinely_invalid_child_still_fails_the_parent(): void
    {
        $registry = new ElementSchemaRegistry();
        $block = [
            'type' => 'section',
            'props' => [],
            'blocks' => [
                ['type' => 'heading', 'props' => ['text' => 'Nested', 'level' => 'not-a-level']],
            ],
        ];

        $this->assertFalse($registry->validate($block));
    }

    // ── E. real nested render: extension container -> heading child ──────

    public function test_nested_string_level_heading_renders_correctly_inside_an_extension_container(): void
    {
        $this->registerDemoSuite();

        $block = [
            'id' => 'container_1',
            'type' => 'ext:acme:demo_container',
            'props' => [],
            'children' => [
                ['id' => 'heading_1', 'type' => 'heading', 'props' => ['text' => 'Nested Heading', 'level' => '2']],
                ['id' => 'card_1', 'type' => 'ext:acme:demo_card', 'props' => ['title' => 'Nested Card', 'content' => 'x']],
            ],
        ];

        $html = app(ThemeCanvasRenderer::class)->renderBlock($block);

        $this->assertStringNotContainsString('[invalid]', $html);
        $this->assertStringContainsString('ext-acme-demo-container', $html);
        $this->assertStringContainsString('<h2', $html);
        $this->assertStringContainsString('Nested Heading', $html);
        $this->assertStringContainsString('ext-acme-demo-card', $html);
    }

    // ── F. out-of-range but well-formed levels never produce malformed markup ──

    public function test_out_of_range_string_level_renders_a_safe_clamped_heading_not_malformed_markup(): void
    {
        $registry = new ElementSchemaRegistry();
        $block = ['id' => 'h1', 'type' => 'heading', 'props' => ['text' => 'X', 'level' => '0']];

        // Outer gate: a well-formed integer string, in range or not, is a
        // syntactically valid value — range clamping is the existing,
        // unmodified inner-layer concern (ElementValidator's enum
        // fallback-to-default + HeadingElement::render()'s own clamp).
        $this->assertTrue($registry->validate($block));

        $html = app(\App\Services\ThemeBuilder\BlockRenderer::class)->renderBlock($block);
        $this->assertMatchesRegularExpression('/<h[1-6][\s>]/', $html);
        $this->assertStringNotContainsString('<h0', $html);
        $this->assertStringNotContainsString('<h7', $html);
    }
}

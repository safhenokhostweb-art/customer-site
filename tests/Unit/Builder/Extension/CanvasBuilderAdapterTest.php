<?php

namespace Tests\Unit\Builder\Extension;

use App\Builder\Definition\ThemeBuilderElementDefinition;
use App\Builder\Definition\ThemeBuilderExtensionBlock;
use App\Builder\Definition\ThemeBuilderExtensionDefinition;
use App\Builder\ElementRegistry;
use App\Builder\Extension\CanvasBuilderAdapter;
use App\Builder\Extension\ThemeBuilderExtensionRegistrar;
use App\Services\ThemeCanvas\BlockRegistry as ThemeCanvasBlockRegistry;
use App\Services\ThemeCanvas\Extension\ExtensionBlockTypeRegistry;
use App\Services\ThemeCanvas\Extension\ExtensionProductIdentity;
use Tests\TestCase;
use Tests\Unit\Builder\Extension\Fixtures\DemoCardElement;
use Tests\Unit\Builder\Extension\Fixtures\DemoContainerElement;

/**
 * Phase 3F-C — proves CanvasBuilderAdapter bridges registered Extension SDK
 * definitions into the exact per-type shape ThemeCanvas\BlockRegistry::all()
 * already uses (name/category/schema/defaultProps), reusing
 * ProductionBuilderAdapter's control-type mapping rather than a second copy.
 */
class CanvasBuilderAdapterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        ThemeCanvasBlockRegistry::reset();
    }

    protected function tearDown(): void
    {
        ExtensionBlockTypeRegistry::reset();
        ElementRegistry::clearCache();
        ThemeCanvasBlockRegistry::reset();
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

    public function test_no_registered_extensions_produces_empty_palette(): void
    {
        $this->assertSame([], CanvasBuilderAdapter::palette());
    }

    public function test_registered_extension_types_appear_with_correct_shape(): void
    {
        $this->registerDemoSuite();

        $palette = CanvasBuilderAdapter::palette();

        $this->assertArrayHasKey('ext:acme:demo_card', $palette);
        $this->assertArrayHasKey('ext:acme:demo_container', $palette);

        $card = $palette['ext:acme:demo_card'];
        $this->assertSame('Acme Demo Card', $card['name']);
        $this->assertSame('content', $card['category']);
        $this->assertArrayHasKey('title', $card['schema']);
        $this->assertArrayHasKey('content', $card['schema']);
        $this->assertSame('string', $card['schema']['title']['type']);
        $this->assertSame('textarea', $card['schema']['content']['type']);
        $this->assertSame(['title' => '', 'content' => ''], $card['defaultProps']);
    }

    public function test_bare_and_module_types_are_never_produced(): void
    {
        $this->registerDemoSuite();

        $palette = CanvasBuilderAdapter::palette();

        foreach (array_keys($palette) as $type) {
            $this->assertStringStartsWith('ext:', $type, "{$type} must be ext:-prefixed — CanvasBuilderAdapter is extension-only");
        }
    }

    public function test_palette_merges_into_theme_canvas_block_registry_without_collision(): void
    {
        $this->registerDemoSuite();

        $all = ThemeCanvasBlockRegistry::all();

        $this->assertArrayHasKey('ext:acme:demo_card', $all);
        $this->assertArrayHasKey('ext:acme:demo_container', $all);
        // Every pre-existing built-in/canonical/module entry must still be present.
        $this->assertArrayHasKey('section', $all);
        $this->assertArrayHasKey('module:blog_posts', $all);
    }

    public function test_container_definition_is_recognized_as_container_by_this_domain(): void
    {
        $this->registerDemoSuite();

        $this->assertTrue(ThemeCanvasBlockRegistry::isContainerType('ext:acme:demo_container'));
        $this->assertFalse(ThemeCanvasBlockRegistry::isContainerType('ext:acme:demo_card'));
    }

    public function test_unregistered_extension_type_is_absent_from_palette(): void
    {
        // No registration performed.
        $all = ThemeCanvasBlockRegistry::all();
        $this->assertArrayNotHasKey('ext:acme:demo_card', $all);
        $this->assertFalse(ThemeCanvasBlockRegistry::isContainerType('ext:acme:demo_card'));
    }
}

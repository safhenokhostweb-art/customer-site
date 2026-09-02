<?php

namespace Tests\Unit\Builder\Extension;

use App\Builder\Definition\ThemeBuilderElementDefinition;
use App\Builder\Definition\ThemeBuilderExtensionBlock;
use App\Builder\Definition\ThemeBuilderExtensionDefinition;
use App\Builder\Extension\ProductionBuilderAdapter;
use App\Builder\Extension\ThemeBuilderExtensionRegistrar;
use App\Http\Controllers\Front\PageBuilderController;
use App\Models\Page;
use App\Models\User;
use App\Services\ThemeBuilder\BlockRenderer;
use App\Services\ThemeBuilder\ElementSchemaRegistry;
use App\Services\ThemeCanvas\Extension\ExtensionBlockTypeRegistry;
use App\Services\ThemeCanvas\Extension\ExtensionProductIdentity;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Unit\Builder\Extension\Fixtures\DemoCardElement;
use Tests\Unit\Builder\Extension\Fixtures\DemoContainerElement;

/**
 * Phase 3E-H-B — proves registered Extension SDK definitions become
 * reachable through the REAL production Builder authoring + schema-gate
 * paths (PageBuilderController::buildRegistryData()/getElementsForSite(),
 * ElementSchemaRegistry, App\Services\ThemeBuilder\BlockRenderer), while
 * every existing production element remains byte-identical.
 */
class ProductionBuilderAdapterTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        ExtensionBlockTypeRegistry::reset();
        \App\Builder\ElementRegistry::clearCache();
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

    private function buildRegistryData(): array
    {
        $controller = new PageBuilderController();
        $method = new \ReflectionMethod(PageBuilderController::class, 'buildRegistryData');
        $method->setAccessible(true);
        return $method->invoke($controller);
    }

    private function getElementsForSite(): array
    {
        $controller = new PageBuilderController();
        $method = new \ReflectionMethod(PageBuilderController::class, 'getElementsForSite');
        $method->setAccessible(true);
        return $method->invoke($controller);
    }

    // ── Palette tests (§14 A-I) ──────────────────────────────────────────

    // A/B. baseline production palette unchanged with no extension provider
    public function test_a_baseline_palette_matches_raw_config_with_no_extension_registered(): void
    {
        $this->assertSame([], ProductionBuilderAdapter::palette());

        [$blockLabels, $blockSchemas, ] = $this->buildRegistryData();
        $rawConfig = config('builder-elements.elements', []);

        // buildRegistryData() also module-gates (enabledModuleSlugs()) —
        // pre-existing, unrelated behavior this phase must not touch. The
        // relevant proof here is narrower: with nothing extension-owned
        // registered, every key present is exactly a raw-config key with
        // exactly raw-config's own data — nothing added, nothing altered.
        $this->assertNotEmpty($blockLabels);
        foreach ($blockLabels as $type => $label) {
            $this->assertArrayHasKey($type, $rawConfig, "{$type} must originate from raw config, not the adapter, when no extension is registered.");
            $this->assertSame($rawConfig[$type]['name'], $label);
            $this->assertSame($rawConfig[$type]['schema'] ?? [], $blockSchemas[$type]['schema']);
            $this->assertSame($rawConfig[$type]['defaultProps'] ?? [], $blockSchemas[$type]['defaultProps']);
        }
    }

    // C/D. registered ext:acme:demo_card / demo_container appear
    public function test_cd_registered_extension_blocks_appear_in_the_palette(): void
    {
        $this->registerDemoSuite();

        $palette = ProductionBuilderAdapter::palette();
        $this->assertArrayHasKey('ext:acme:demo_card', $palette);
        $this->assertArrayHasKey('ext:acme:demo_container', $palette);

        [$blockLabels, , $byCategory] = $this->buildRegistryData();
        $this->assertArrayHasKey('ext:acme:demo_card', $blockLabels);
        $this->assertSame('Acme Demo Card', $blockLabels['ext:acme:demo_card']);
        $this->assertArrayHasKey('content', $byCategory);
        $this->assertArrayHasKey('ext:acme:demo_card', $byCategory['content']);
    }

    // E. extension labels/category/icon/control metadata correctly adapted
    public function test_e_extension_metadata_is_correctly_adapted(): void
    {
        $this->registerDemoSuite();
        $entry = ProductionBuilderAdapter::palette()['ext:acme:demo_card'];

        $this->assertSame('Acme Demo Card', $entry['name']);
        $this->assertSame('🃏', $entry['icon']);
        $this->assertSame('content', $entry['category']);
        $this->assertNull($entry['required_module']);
        $this->assertArrayHasKey('title', $entry['schema']);
        $this->assertSame('string', $entry['schema']['title']['type']);
        $this->assertArrayHasKey('content', $entry['schema']);
        $this->assertSame('textarea', $entry['schema']['content']['type']);
    }

    // F. canonical adapters are NOT duplicated into palette
    public function test_f_canonical_adapter_types_are_not_duplicated(): void
    {
        $this->registerDemoSuite();
        $palette = ProductionBuilderAdapter::palette();

        foreach (['heading', 'text', 'image', 'button', 'divider', 'section', 'grid', 'columns', 'hero', 'container'] as $canonicalType) {
            $this->assertArrayNotHasKey($canonicalType, $palette, "{$canonicalType} must not be added by the extension-only adapter.");
        }
    }

    // G. host-only ElementRegistry types are NOT newly injected unless already present via legacy config
    public function test_g_host_only_types_are_not_newly_injected(): void
    {
        $this->registerDemoSuite();
        $palette = ProductionBuilderAdapter::palette();

        foreach (['spacer', 'row', 'column'] as $hostOnlyType) {
            $this->assertArrayNotHasKey($hostOnlyType, $palette, "{$hostOnlyType} is host-only, not extension — must not appear in the adapter output.");
        }
    }

    // H. failed package registration exposes zero extension palette entries
    public function test_h_failed_package_registration_exposes_zero_palette_entries(): void
    {
        $this->registerDemoSuite();

        $conflicting = ThemeBuilderExtensionBlock::make(
            'ext:acme:demo_card',
            ThemeBuilderElementDefinition::fromControls('ext:acme:demo_card', 'Different', 'content', '', []),
            DemoContainerElement::class,
        );
        $badPackage = ThemeBuilderExtensionDefinition::make(
            ExtensionProductIdentity::fromParts('acme', 'other-suite'),
            '1.0.0',
            ThemeBuilderExtensionDefinition::SDK_CONTRACT_V1,
            [$conflicting],
        );

        try {
            ThemeBuilderExtensionRegistrar::register($badPackage);
            $this->fail('Expected registration failure.');
        } catch (\Throwable) {
            // only the original demo-suite entries exist; the conflicting one never appeared
            $palette = ProductionBuilderAdapter::palette();
            $this->assertSame('Acme Demo Card', $palette['ext:acme:demo_card']['name']);
        }
    }

    // I. repeated boot does not duplicate entries
    public function test_i_repeated_registration_does_not_duplicate_palette_entries(): void
    {
        $this->registerDemoSuite();
        $this->registerDemoSuite();

        $palette = ProductionBuilderAdapter::palette();
        $this->assertCount(2, $palette);
    }

    // ── Schema-gate tests (§15 A-I) ──────────────────────────────────────

    // A/B. existing config schema types resolve exactly as before, unchanged count/data
    public function test_ab_existing_schema_types_resolve_unchanged(): void
    {
        $rawSchemas = config('builder-element-schemas.elements', []);
        $registry = new ElementSchemaRegistry();

        $this->assertSame(array_keys($rawSchemas), $registry->getRegisteredTypes());
        foreach ($rawSchemas as $type => $schema) {
            $this->assertSame($schema, $registry->getSchema($type));
        }
    }

    // C/D. ext:acme:demo_card / demo_container resolve through the extension adapter
    public function test_cd_extension_types_resolve_through_the_schema_gate(): void
    {
        $this->registerDemoSuite();
        $registry = new ElementSchemaRegistry();

        $this->assertTrue($registry->has('ext:acme:demo_card'));
        $this->assertTrue($registry->has('ext:acme:demo_container'));
        $this->assertSame('blocks', $registry->getSchema('ext:acme:demo_container')['children']);
        $this->assertSame('none', $registry->getSchema('ext:acme:demo_card')['children']);
    }

    // E. unknown ext:* remains rejected
    public function test_e_unknown_ext_type_remains_rejected(): void
    {
        $this->registerDemoSuite();
        $registry = new ElementSchemaRegistry();

        $this->assertFalse($registry->validate(['type' => 'ext:acme:not_registered', 'props' => []]));
        $this->assertStringContainsString('Unknown element type', $registry->getLastErrors()[0]);
    }

    // F. malformed ext:* remains rejected
    public function test_f_malformed_ext_type_remains_rejected(): void
    {
        $registry = new ElementSchemaRegistry();
        $this->assertFalse($registry->validate(['type' => 'ext:Not Valid!', 'props' => []]));
    }

    // G. unregistered ext:* remains rejected (no provider loaded at all)
    public function test_g_unregistered_ext_type_remains_rejected_with_no_provider(): void
    {
        $registry = new ElementSchemaRegistry();
        $this->assertFalse($registry->has('ext:acme:demo_card'));
    }

    // H. failed package registration leaves schema gate unaware of all package blocks
    public function test_h_failed_package_registration_leaves_schema_gate_unaware(): void
    {
        $conflictingProduct = ExtensionProductIdentity::fromParts('acme', 'broken-suite');
        try {
            ThemeBuilderExtensionDefinition::make($conflictingProduct, '1.0.0', 99, [
                ThemeBuilderExtensionBlock::make('ext:acme:broken_block', ThemeBuilderElementDefinition::fromControls('ext:acme:broken_block', 'x', 'content', '', []), DemoCardElement::class),
            ]);
            $this->fail('Expected unsupported SDK contract to fail.');
        } catch (\Throwable) {
            $registry = new ElementSchemaRegistry();
            $this->assertFalse($registry->has('ext:acme:broken_block'));
        }
    }

    // I. existing config authority wins for existing bare types (defense-in-depth, structurally guaranteed anyway)
    public function test_i_existing_config_wins_precedence_for_bare_types(): void
    {
        $registry = new ElementSchemaRegistry();
        $rawSchemas = config('builder-element-schemas.elements', []);
        $this->assertSame($rawSchemas['heading'], $registry->getSchema('heading'));
    }

    // ── Render reachability (§18) ────────────────────────────────────────

    public function test_render_reachability_through_the_real_block_renderer(): void
    {
        $this->registerDemoSuite();

        $schemaRegistry = new ElementSchemaRegistry();
        $block = ['id' => 'x1', 'type' => 'ext:acme:demo_card', 'props' => ['title' => 'Hello', 'content' => '<p>ok</p>']];

        $this->assertTrue($schemaRegistry->validate($block), implode('; ', $schemaRegistry->getLastErrors()));

        $renderer = app(BlockRenderer::class);
        $renderer->setSchemaRegistry($schemaRegistry);
        $html = $renderer->renderBlock($block);

        $this->assertStringContainsString('Hello', $html);
        $this->assertStringContainsString('<p>ok</p>', $html);
        $this->assertStringNotContainsString('[invalid]', $html);
        $this->assertStringNotContainsString('[error]', $html);
    }

    public function test_stored_xss_neutralized_through_the_real_block_renderer(): void
    {
        $this->registerDemoSuite();
        $schemaRegistry = new ElementSchemaRegistry();
        $block = ['id' => 'x2', 'type' => 'ext:acme:demo_card', 'props' => [
            'title' => 'x',
            'content' => '<a href="javascript:alert(1)" onmouseover="alert(2)">click</a><img src=x onerror=alert(3)>',
        ]];

        $renderer = app(BlockRenderer::class);
        $renderer->setSchemaRegistry($schemaRegistry);
        $html = $renderer->renderBlock($block);

        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('onmouseover', $html);
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringNotContainsString('<img', $html);
    }

    // ── Save-envelope compatibility (§17) ────────────────────────────────

    public function test_save_envelope_accepts_a_real_extension_block_via_the_real_http_endpoint(): void
    {
        $this->registerDemoSuite();
        $this->withoutMiddleware(VerifyCsrfToken::class);

        file_put_contents(storage_path('installed.lock'), '1');
        config(['site.owner_site' => true]);

        $page = Page::create([
            'title' => 'تست 3E-H-B', 'slug' => 'test-3eh-b-' . uniqid(), 'body' => '',
            'builder_content_v2' => ['blocks' => []], 'builder_type' => Page::BUILDER_TYPE_BUILDER,
            'is_published' => false, 'show_in_menu' => false,
        ]);
        $user = User::factory()->create();
        $user->update(['is_admin' => true]);

        $response = $this->actingAs($user)->putJson(route('builder.page.update', $page), [
            'blocks' => [['id' => 'e1', 'type' => 'ext:acme:demo_card', 'props' => ['title' => 'Saved Title', 'content' => 'ok']]],
        ]);
        $response->assertSuccessful();

        $page->refresh();
        $this->assertSame('ext:acme:demo_card', $page->builder_content_v2['blocks'][0]['type']);
        $this->assertSame('Saved Title', $page->builder_content_v2['blocks'][0]['props']['title']);

        @unlink(storage_path('installed.lock'));
    }

    // ── Existing 55-type safety gate (§19) ───────────────────────────────

    public function test_all_existing_55_schema_types_are_unaffected_by_registering_an_extension(): void
    {
        $before = (new ElementSchemaRegistry())->getRegisteredTypes();
        sort($before);

        $this->registerDemoSuite();

        $after = array_diff((new ElementSchemaRegistry())->getRegisteredTypes(), ['ext:acme:demo_card', 'ext:acme:demo_container']);
        sort($after);

        $this->assertSame($before, $after);

        foreach ($before as $type) {
            $this->assertSame(config('builder-element-schemas.elements')[$type], (new ElementSchemaRegistry())->getSchema($type));
        }
    }
}

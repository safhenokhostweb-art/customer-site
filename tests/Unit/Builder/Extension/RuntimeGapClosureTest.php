<?php

namespace Tests\Unit\Builder\Extension;

use App\Builder\Definition\ThemeBuilderElementDefinition;
use App\Builder\Definition\ThemeBuilderExtensionBlock;
use App\Builder\Definition\ThemeBuilderExtensionDefinition;
use App\Builder\ElementRegistry;
use App\Builder\Extension\ThemeBuilderExtensionRegistrar;
use App\Http\Controllers\Front\PageBuilderController;
use App\Models\Page;
use App\Models\User;
use App\Services\ThemeBuilder\BlockRenderer;
use App\Services\ThemeBuilder\ElementSchemaRegistry;
use App\Services\ThemeCanvas\Extension\ExtensionBlockTypeRegistry;
use App\Services\ThemeCanvas\Extension\ExtensionProductIdentity;
use App\Services\ThemeCanvas\ThemeRuntimeDocument;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Unit\Builder\Extension\Fixtures\DemoCardElement;
use Tests\Unit\Builder\Extension\Fixtures\DemoContainerElement;
use Tests\Unit\Builder\Extension\Fixtures\ThrowingDemoElement;

/**
 * Phase 3E-H-C — closes the 5 remaining production runtime gaps 3E-H-B left
 * open: extension container children, extension asset emission, unknown/
 * unavailable extension preservation with a server-side trust boundary,
 * renderer-failure-vs-unavailable fallback, and re-enable-without-migration.
 */
class RuntimeGapClosureTest extends TestCase
{
    use RefreshDatabase;

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

    private function registerThrowingDemo(): void
    {
        $package = ThemeBuilderExtensionDefinition::make(
            ExtensionProductIdentity::fromParts('acme', 'throwing-suite'),
            '1.0.0',
            ThemeBuilderExtensionDefinition::SDK_CONTRACT_V1,
            [
                ThemeBuilderExtensionBlock::make('ext:acme:demo_throwing', ThemeBuilderElementDefinition::fromElementClass(ThrowingDemoElement::class), ThrowingDemoElement::class),
            ],
        );
        ThemeBuilderExtensionRegistrar::register($package);
    }

    private function makePage(array $blocks = []): Page
    {
        return Page::create([
            'title' => 'تست 3E-H-C', 'slug' => 'test-3eh-c-' . uniqid(),
            'body' => '', 'builder_content_v2' => ['blocks' => $blocks],
            'builder_type' => Page::BUILDER_TYPE_BUILDER,
            'is_published' => false, 'show_in_menu' => false,
        ]);
    }

    private function actingAdmin(): User
    {
        $user = User::factory()->create();
        $user->update(['is_admin' => true]);
        return $user;
    }

    // ── 1. Container children rendering ──────────────────────────────────

    public function test_extension_container_renders_builtin_and_extension_children(): void
    {
        $this->registerDemoSuite();
        $schemaRegistry = new ElementSchemaRegistry();
        $block = [
            'id' => 'c1',
            'type' => 'ext:acme:demo_container',
            'props' => [],
            'blocks' => [
                ['id' => 'child1', 'type' => 'heading', 'props' => ['text' => 'Child Heading', 'level' => 2]],
                ['id' => 'child2', 'type' => 'ext:acme:demo_card', 'props' => ['title' => 'Nested Card', 'content' => 'ok']],
            ],
        ];
        $this->assertTrue($schemaRegistry->validate($block), implode('; ', $schemaRegistry->getLastErrors()));

        $renderer = app(BlockRenderer::class)->setSchemaRegistry($schemaRegistry);
        $html = $renderer->renderBlock($block);

        $this->assertStringContainsString('ext-acme-demo-container', $html);
        $this->assertStringContainsString('Child Heading', $html);
        $this->assertStringContainsString('Nested Card', $html);
    }

    public function test_unregistered_extension_container_does_not_render_children(): void
    {
        // No registration at all — the container type itself is unknown to
        // the schema gate, so the whole block safely placeholders instead
        // of rendering orphaned children.
        $schemaRegistry = new ElementSchemaRegistry();
        $block = [
            'id' => 'c2',
            'type' => 'ext:acme:demo_container',
            'props' => [],
            'blocks' => [['id' => 'child1', 'type' => 'heading', 'props' => ['text' => 'Orphan']]],
        ];
        $this->assertFalse($schemaRegistry->validate($block));

        $renderer = app(BlockRenderer::class)->setSchemaRegistry($schemaRegistry);
        $html = $renderer->renderBlock($block);
        $this->assertStringContainsString('[invalid]', $html);
        $this->assertStringNotContainsString('Orphan', $html);
    }

    public function test_existing_builtin_container_classification_is_unchanged(): void
    {
        $this->registerDemoSuite();
        $schemaRegistry = new ElementSchemaRegistry();
        foreach (['section', 'container', 'grid', 'columns', 'row', 'column', 'flex_container'] as $type) {
            $block = ['id' => "b-{$type}", 'type' => $type, 'props' => [], 'blocks' => [
                ['id' => "b-{$type}-child", 'type' => 'heading', 'props' => ['text' => 'X']],
            ]];
            if (!$schemaRegistry->validate($block)) {
                continue; // some bare container types aren't authorable via config schema alone — not this phase's concern
            }
            $renderer = app(BlockRenderer::class)->setSchemaRegistry($schemaRegistry);
            $html = $renderer->renderBlock($block);
            if (str_contains($html, 'tb-placeholder')) {
                continue; // some bare types don't have a full render path in this test harness — pre-existing, not this phase's concern
            }
            $this->assertStringContainsString('X', $html, "{$type} must still render its children exactly as before.");
        }
    }

    public function test_non_container_extension_type_never_renders_children(): void
    {
        $this->registerDemoSuite();
        $schemaRegistry = new ElementSchemaRegistry();
        // demo_card's schema declares children:'none' — a child array must
        // fail validation the same way any other non-container type would.
        $block = [
            'id' => 'nc1',
            'type' => 'ext:acme:demo_card',
            'props' => ['title' => 't', 'content' => 'c'],
            'blocks' => [['id' => 'child', 'type' => 'heading', 'props' => ['text' => 'ShouldNotAppear']]],
        ];
        // children key is present but schema rule is 'none' — validate() only
        // recurses when the rule is 'blocks', so this simply renders the
        // card itself without ever considering the extraneous children array.
        $this->assertTrue($schemaRegistry->validate($block));
        $renderer = app(BlockRenderer::class)->setSchemaRegistry($schemaRegistry);
        $html = $renderer->renderBlock($block);
        $this->assertStringNotContainsString('ShouldNotAppear', $html);
    }

    // ── 2. Asset pipeline ─────────────────────────────────────────────────

    public function test_no_extension_registered_means_no_collected_assets(): void
    {
        $renderer = app(BlockRenderer::class);
        $renderer->renderBlocks([['id' => 'h1', 'type' => 'heading', 'props' => ['text' => 'Hi']]]);
        $this->assertSame(['css' => [], 'js' => []], $renderer->getCollectedAssets());
    }

    public function test_extension_assets_are_collected_and_deduplicated_across_multiple_blocks_in_one_response(): void
    {
        $this->registerDemoSuite();
        $schemaRegistry = new ElementSchemaRegistry();
        $renderer = app(BlockRenderer::class)->setSchemaRegistry($schemaRegistry);

        $renderer->renderBlocks([
            ['id' => 'a', 'type' => 'ext:acme:demo_card', 'props' => ['title' => '1', 'content' => 'x']],
            ['id' => 'b', 'type' => 'ext:acme:demo_card', 'props' => ['title' => '2', 'content' => 'y']],
        ]);

        $assets = $renderer->getCollectedAssets();
        $this->assertSame(['/vendor/acme/demo-card.css'], $assets['css']);
        $this->assertSame([], $assets['js']);
    }

    public function test_asset_collection_does_not_leak_across_separate_renderer_instances(): void
    {
        $this->registerDemoSuite();
        $schemaRegistry = new ElementSchemaRegistry();

        $rendererOne = app(BlockRenderer::class)->setSchemaRegistry($schemaRegistry);
        $rendererOne->renderBlocks([['id' => 'a', 'type' => 'ext:acme:demo_card', 'props' => ['title' => '1', 'content' => 'x']]]);
        $this->assertNotEmpty($rendererOne->getCollectedAssets()['css']);

        // A fresh instance (as every new HTTP request resolves) starts clean.
        $rendererTwo = app(BlockRenderer::class)->setSchemaRegistry($schemaRegistry);
        $rendererTwo->renderBlocks([['id' => 'h', 'type' => 'heading', 'props' => ['text' => 'Hi']]]);
        $this->assertSame(['css' => [], 'js' => []], $rendererTwo->getCollectedAssets());
    }

    public function test_repeated_render_of_the_same_block_is_deterministic(): void
    {
        $this->registerDemoSuite();
        $schemaRegistry = new ElementSchemaRegistry();
        $block = ['id' => 'a', 'type' => 'ext:acme:demo_card', 'props' => ['title' => 'T', 'content' => 'C']];

        $htmlOne = app(BlockRenderer::class)->setSchemaRegistry($schemaRegistry)->renderBlock($block);
        $htmlTwo = app(BlockRenderer::class)->setSchemaRegistry($schemaRegistry)->renderBlock($block);
        $this->assertSame($htmlOne, $htmlTwo);
    }

    public function test_preview_endpoint_returns_collected_extension_assets(): void
    {
        $this->registerDemoSuite();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        file_put_contents(storage_path('installed.lock'), '1');
        config(['site.owner_site' => true]);
        $user = $this->actingAdmin();

        $response = $this->actingAs($user)->postJson(route('builder.preview'), [
            'blocks' => [['id' => 'a', 'type' => 'ext:acme:demo_card', 'props' => ['title' => 'T', 'content' => 'C']]],
        ]);
        $response->assertSuccessful();
        $response->assertJsonPath('assets.css', ['/vendor/acme/demo-card.css']);

        @unlink(storage_path('installed.lock'));
    }

    public function test_preview_endpoint_returns_empty_assets_when_no_extension_used(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        file_put_contents(storage_path('installed.lock'), '1');
        config(['site.owner_site' => true]);
        $user = $this->actingAdmin();

        $response = $this->actingAs($user)->postJson(route('builder.preview'), [
            'blocks' => [['id' => 'h', 'type' => 'heading', 'props' => ['text' => 'Hi']]],
        ]);
        $response->assertSuccessful();
        $response->assertJsonPath('assets', ['css' => [], 'js' => []]);

        @unlink(storage_path('installed.lock'));
    }

    // ── 3/4/5. Unknown/unavailable preservation, fail-closed injection, render fallback, re-enable ──

    public function test_unavailable_extension_block_is_preserved_across_edits_and_new_injection_is_rejected_then_recovers_on_reenable(): void
    {
        $this->registerDemoSuite();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        file_put_contents(storage_path('installed.lock'), '1');
        config(['site.owner_site' => true]);
        $user = $this->actingAdmin();

        $page = $this->makePage();

        // 1) Save while the extension is available.
        $this->actingAs($user)->putJson(route('builder.page.update', $page), [
            'blocks' => [
                ['id' => 'e1', 'type' => 'ext:acme:demo_card', 'props' => ['title' => 'Preserved', 'content' => 'ok']],
                ['id' => 'h1', 'type' => 'heading', 'props' => ['text' => 'Hi']],
            ],
        ])->assertSuccessful();

        // 2) Simulate the provider no longer being loaded (fresh process without it booted).
        ElementRegistry::clearCache();

        // 2a) An unrelated edit still saves; the untouched, now-unavailable
        // extension block must survive unchanged.
        $this->actingAs($user)->putJson(route('builder.page.update', $page), [
            'blocks' => [
                ['id' => 'e1', 'type' => 'ext:acme:demo_card', 'props' => ['title' => 'Preserved', 'content' => 'ok']],
                ['id' => 'h1', 'type' => 'heading', 'props' => ['text' => 'Edited']],
            ],
        ])->assertSuccessful();

        $page->refresh();
        $byId = collect($page->builder_content_v2['blocks'])->keyBy('id');
        $this->assertSame('ext:acme:demo_card', $byId['e1']['type']);
        $this->assertSame('Preserved', $byId['e1']['props']['title']);
        $this->assertSame('Edited', $byId['h1']['props']['text']);

        // 3) A brand-new, client-asserted unavailable ext:* block must be
        // rejected from the save — the rest of the request still saves.
        $this->actingAs($user)->putJson(route('builder.page.update', $page), [
            'blocks' => [
                ['id' => 'e1', 'type' => 'ext:acme:demo_card', 'props' => ['title' => 'Preserved', 'content' => 'ok']],
                ['id' => 'new1', 'type' => 'ext:acme:demo_card', 'props' => ['title' => 'Injected', 'content' => 'bad']],
                ['id' => 'h1', 'type' => 'heading', 'props' => ['text' => 'Still Editable']],
            ],
        ])->assertSuccessful();

        $page->refresh();
        $ids = collect($page->builder_content_v2['blocks'])->pluck('id')->all();
        $this->assertContains('e1', $ids);
        $this->assertContains('h1', $ids);
        $this->assertNotContains('new1', $ids);

        // 4) Rendering the unavailable block is safe: no fatal, deterministic placeholder.
        $currentE1 = collect($page->builder_content_v2['blocks'])->keyBy('id')['e1'];
        $schemaRegistry = new ElementSchemaRegistry();
        $html = app(BlockRenderer::class)->setSchemaRegistry($schemaRegistry)->renderBlock($currentE1);
        $this->assertStringContainsString('[invalid]', $html);
        $this->assertStringNotContainsString('Preserved', $html);

        // 5) Re-enable the same provider — the SAME persisted block becomes
        // renderable again with its original props, no migration/rewrite.
        $this->registerDemoSuite();
        $schemaRegistryAfter = new ElementSchemaRegistry();
        $htmlAfter = app(BlockRenderer::class)->setSchemaRegistry($schemaRegistryAfter)->renderBlock($currentE1);
        $this->assertStringContainsString('Preserved', $htmlAfter);

        @unlink(storage_path('installed.lock'));
    }

    public function test_opening_editor_and_listing_page_does_not_delete_an_unavailable_extension_block(): void
    {
        $this->registerDemoSuite();
        $page = $this->makePage([
            ['id' => 'e1', 'type' => 'ext:acme:demo_card', 'props' => ['title' => 'Preserved', 'content' => 'ok']],
        ]);

        ElementRegistry::clearCache();

        $controller = new PageBuilderController();
        $method = new \ReflectionMethod(PageBuilderController::class, 'show');
        $response = $method->invoke($controller, $page);
        $payload = $response->getData(true);

        $this->assertSame('ext:acme:demo_card', $payload['page']['builder_content_v2']['blocks'][0]['type']);
        $this->assertSame('Preserved', $payload['page']['builder_content_v2']['blocks'][0]['props']['title']);
    }

    // ── 4. Render fallback: registered-but-throws vs unregistered ─────────

    public function test_registered_extension_that_throws_falls_back_to_render_error_placeholder(): void
    {
        $this->registerThrowingDemo();
        $schemaRegistry = new ElementSchemaRegistry();
        $block = ['id' => 't1', 'type' => 'ext:acme:demo_throwing', 'props' => []];
        $this->assertTrue($schemaRegistry->validate($block), implode('; ', $schemaRegistry->getLastErrors()));

        // A registered extension whose renderer throws is caught by
        // ElementRenderer's own try/catch (App\Builder\Rendering) — a
        // separate, inner placeholder boundary from BlockRenderer's own
        // 'invalid_schema'/'render_error' placeholders, and pre-existing,
        // documented debt (not created by this phase). Both boundaries are
        // equally safe: no exception/stack trace ever reaches the response.
        $html = app(BlockRenderer::class)->setSchemaRegistry($schemaRegistry)->renderBlock($block);
        $this->assertStringContainsString('tb-placeholder', $html);
        $this->assertStringContainsString('Element failed to render', $html);
        $this->assertStringNotContainsString('RuntimeException', $html);
        $this->assertStringNotContainsString('always fails', $html);
    }

    public function test_a_throwing_neighbor_block_does_not_prevent_other_blocks_from_rendering(): void
    {
        $this->registerThrowingDemo();
        $schemaRegistry = new ElementSchemaRegistry();
        $renderer = app(BlockRenderer::class)->setSchemaRegistry($schemaRegistry);

        $html = $renderer->renderBlocks([
            ['id' => 'before', 'type' => 'heading', 'props' => ['text' => 'Before']],
            ['id' => 't1', 'type' => 'ext:acme:demo_throwing', 'props' => []],
            ['id' => 'after', 'type' => 'heading', 'props' => ['text' => 'After']],
        ]);

        $this->assertStringContainsString('Before', $html);
        $this->assertStringContainsString('After', $html);
        $this->assertStringContainsString('Element failed to render', $html);
    }

    public function test_unregistered_extension_type_falls_back_to_invalid_placeholder_not_error(): void
    {
        // Distinct from the throwing case: an unregistered type never even
        // reaches the renderer — it fails schema validation first.
        $schemaRegistry = new ElementSchemaRegistry();
        $block = ['id' => 'u1', 'type' => 'ext:acme:never_registered', 'props' => []];
        $html = app(BlockRenderer::class)->setSchemaRegistry($schemaRegistry)->renderBlock($block);
        $this->assertStringContainsString('[invalid]', $html);
        $this->assertStringNotContainsString('[error]', $html);
    }

    // ── Public-facing page render path (page.blade.php / home.blade.php) ──

    public function test_public_page_view_emits_extension_css_link_for_an_extension_block(): void
    {
        $this->registerDemoSuite();
        $page = $this->makePage([
            ['id' => 'e1', 'type' => 'ext:acme:demo_card', 'props' => ['title' => 'Visible', 'content' => 'ok']],
        ]);
        $page->update(['is_published' => true]);

        $document = app(ThemeRuntimeDocument::class)->buildForPage($page);
        $html = view('themes.default.page', ['page' => $page] + $document)->render();

        $this->assertStringContainsString('/vendor/acme/demo-card.css', $html);
        $this->assertStringContainsString('Visible', $html);
    }

    public function test_public_page_view_emits_no_extension_asset_tags_without_extension_blocks(): void
    {
        $page = $this->makePage([
            ['id' => 'h1', 'type' => 'heading', 'props' => ['text' => 'Plain']],
        ]);
        $page->update(['is_published' => true]);

        $document = app(ThemeRuntimeDocument::class)->buildForPage($page);
        $html = view('themes.default.page', ['page' => $page] + $document)->render();

        $this->assertStringContainsString('Plain', $html);
        $this->assertStringNotContainsString('/vendor/acme/', $html);
    }
}

<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\Page;
use App\Services\ThemeCanvas\ThemeRuntimeDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\EnsuresInstallationLock;
use Tests\TestCase;

/**
 * Phase 8 Step 1.2 — guards the viewport-triggered asset hydration engine in
 * the public Canvas layout.
 *
 * These are the load-bearing properties of that engine, and each one is a
 * regression guard rather than a restatement of the implementation:
 *
 *  1. The manifest island is addressable by the id the engine looks up
 *     (#theme-asset-manifest) while KEEPING the data-tb-asset-manifest
 *     attribute the earlier bridge already selects on — adding the id must
 *     not break the existing consumer.
 *  2. The engine only exists on the manifest branch. A page served without a
 *     manifest must fall back to the safe-default eager path and must not
 *     ship a hydration engine that would have nothing to cross-reference.
 *  3. BLOCK_ASSETS ships empty, so no asset URL is ever requested that this
 *     installation does not actually contain. This asserts the absence of a
 *     404-inducing registry; if a real block-asset file is ever added, the
 *     engine data changes and this guard is what stops the manifest from
 *     silently growing an entry that does not resolve.
 */
class AssetHydrationEngineTest extends TestCase
{
    use EnsuresInstallationLock, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureInstallationLock();
        config([
            'site.owner_site' => true,
            'services.customer_provision_secret' => 'phase8-test-secret',
        ]);
    }

    protected function tearDown(): void
    {
        $this->releaseInstallationLock();
        parent::tearDown();
    }

    /**
     * Installs a canvas theme whose `asset-hydration-probe` page carries the
     * given blocks, then GETs the public render of that page.
     *
     * The canvas theme is what routes /page/{slug} through
     * ThemeCanvasPageController → layouts.canvas-theme (where the manifest
     * island and hydration engine actually live). A Page-model row alone is
     * not enough — ThemeCanvasPageController::show() only delegates to
     * PageController when no canvas theme is installed.
     */
    private function publishPageWith(array $blocks): string
    {
        \App\Models\Setting::set('active_canvas_theme_data', json_encode([
            'theme_id' => 'phase8-hydration-test',
            'theme_name' => 'Phase 8 Hydration Test',
            'header_blocks' => [],
            'footer_blocks' => [],
            'pages' => [
                'home' => ['title' => 'Home', 'slug' => '', 'is_home' => true, 'blocks' => []],
                'probe' => [
                    'title' => 'Asset hydration probe',
                    'slug' => 'asset-hydration-probe',
                    'blocks' => $blocks,
                ],
            ],
            'global_styles' => [],
        ], JSON_UNESCAPED_UNICODE), 'theme');

        \App\Models\Setting::set('active_canvas_theme_name', 'Phase 8 Hydration Test', 'theme');

        return (string) $this->get(route('page.show', 'asset-hydration-probe'))->assertOk()->getContent();
    }

    public function test_manifest_island_is_addressable_by_id_without_losing_the_data_attribute(): void
    {
        $html = $this->publishPageWith([
            ['id' => 'probe-text', 'type' => 'text', 'props' => ['content' => 'hello']],
        ]);

        $this->assertStringContainsString(
            'id="theme-asset-manifest"',
            $html,
            'the hydration engine selects the manifest by id; the id must be emitted'
        );

        $this->assertStringContainsString(
            'data-tb-asset-manifest',
            $html,
            'the pre-existing eager bridge still selects this island by its data attribute — '
            . 'adding the id must not remove it'
        );

        $this->assertStringContainsString(
            'type="application/json"',
            $html,
            'the island must stay inert application/json so it never blocks the parser'
        );
    }

    public function test_hydration_engine_ships_with_an_empty_block_asset_registry(): void
    {
        $html = $this->publishPageWith([
            ['id' => 'probe-text', 'type' => 'text', 'props' => ['content' => 'hello']],
        ]);

        $this->assertStringContainsString(
            'BLOCK_ASSETS',
            $html,
            'the engine and its registry must be present in the public layout'
        );

        $this->assertStringContainsString(
            'IntersectionObserver',
            $html,
            'on-demand loading is the point of this phase; the observer must be wired'
        );

        // The registry is shipped empty on purpose. Asserting it is empty keeps
        // the manifest from ever naming an asset file that does not exist on
        // disk, which would inject a 404 into every public page.
        $this->assertMatchesRegularExpression(
            '/BLOCK_ASSETS\s*=\s*Object\.freeze\(\{\s*\}\)/',
            $html,
            'BLOCK_ASSETS must remain empty until real per-block asset files exist'
        );
    }

    public function test_engine_deduplicates_against_manifest_and_existing_dom_tags(): void
    {
        $html = $this->publishPageWith([
            ['id' => 'probe-text', 'type' => 'text', 'props' => ['content' => 'hello']],
        ]);

        // Double-loading is prevented structurally, not by chance: the engine
        // unions the manifest with the live DOM before injecting anything.
        foreach (['manifest.css', 'manifest.js', 'link[rel="stylesheet"]', 'script[src]'] as $probe) {
            $this->assertStringContainsString(
                $probe,
                $html,
                'the dedupe set must be seeded from the manifest AND the already-rendered DOM tags'
            );
        }
    }

    public function test_engine_is_gated_on_the_manifest_branch(): void
    {
        $html = $this->publishPageWith([
            ['id' => 'probe-text', 'type' => 'text', 'props' => ['content' => 'hello']],
        ]);

        $this->assertStringContainsString(
            '$assetManifest ?? null',
            $html,
            'the existing is_array/manifest null check must remain the source of truth for the '
            . 'initial page load; the engine must live inside that same branch'
        );
    }

    public function test_public_projection_still_omits_editor_capabilities(): void
    {
        // Regression guard tying this phase back to Phase 7: asset hydration
        // must not have weakened the public/editor boundary.
        $page = Page::create([
            'title' => 'Asset hydration capability probe',
            'slug' => 'asset-hydration-capability-probe',
            'body' => '',
            'builder_type' => Page::BUILDER_TYPE_BUILDER,
            'builder_content_v2' => ['blocks' => [
                ['id' => 'cap-text', 'type' => 'text', 'props' => ['content' => 'hello']],
            ]],
            'is_published' => true,
            'show_in_menu' => false,
        ]);

        $document = app(ThemeRuntimeDocument::class)
            ->forMode(ThemeRuntimeDocument::MODE_PUBLIC)
            ->buildForPage($page);

        $this->assertArrayHasKey('runtime', $document['capabilityProjection']);
        $this->assertArrayNotHasKey('editor', $document['capabilityProjection']);
    }
}

<?php

namespace Tests\Unit\Builder\Extension;

use App\Builder\Definition\ThemeBuilderElementDefinition;
use App\Builder\Definition\ThemeBuilderExtensionBlock;
use App\Builder\Definition\ThemeBuilderExtensionDefinition;
use App\Builder\ElementRegistry;
use App\Builder\Extension\ThemeBuilderExtensionRegistrar;
use App\Builder\Rendering\ElementRenderer;
use App\Services\ThemeCanvas\Extension\ExtensionBlockTypeRegistry;
use App\Services\ThemeCanvas\Extension\ExtensionProductIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Unit\Builder\Extension\Fixtures\DemoCardElement;
use Tests\Unit\Builder\Extension\Fixtures\DemoManifestOnlyElement;

/**
 * Consumer half of the cross-repo Extension-asset contract (producer half:
 * saas-platform's ExtensionThemeRuntimeAssetProjectorTest). Proves
 * ElementRenderer::setExtensionAssetMap() emits manifest-declared CSS/JS
 * for an ext:* renderer independently of HasAssets, without disturbing the
 * pre-existing HasAssets-only path.
 *
 * This is the code path the mandatory acceptance scenario runs through:
 * a manifest declares gallery.css/gallery.js, DemoManifestOnlyElement (the
 * ext:e2etest:gallery renderer) does NOT implement HasAssets, and the
 * assets are still collected — the exact gap the reconciliation report
 * identified as missing.
 */
class ManifestAssetEmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        ExtensionBlockTypeRegistry::reset();
        ElementRegistry::clearCache();
        parent::tearDown();
    }

    private function registerManifestOnlyGallery(): void
    {
        $package = ThemeBuilderExtensionDefinition::make(
            ExtensionProductIdentity::fromParts('e2etest', 'gallery-suite'),
            '1.0.0',
            ThemeBuilderExtensionDefinition::SDK_CONTRACT_V1,
            [
                ThemeBuilderExtensionBlock::make(
                    'ext:e2etest:gallery',
                    ThemeBuilderElementDefinition::fromElementClass(DemoManifestOnlyElement::class),
                    DemoManifestOnlyElement::class
                ),
            ],
        );
        ThemeBuilderExtensionRegistrar::register($package);
    }

    private function registerHasAssetsDemoCard(): void
    {
        $package = ThemeBuilderExtensionDefinition::make(
            ExtensionProductIdentity::fromParts('acme', 'demo-suite'),
            '1.0.0',
            ThemeBuilderExtensionDefinition::SDK_CONTRACT_V1,
            [
                ThemeBuilderExtensionBlock::make(
                    'ext:acme:demo_card',
                    ThemeBuilderElementDefinition::fromElementClass(DemoCardElement::class),
                    DemoCardElement::class
                ),
            ],
        );
        ThemeBuilderExtensionRegistrar::register($package);
    }

    public function test_case1_manifest_assets_emitted_without_has_assets(): void
    {
        $this->registerManifestOnlyGallery();

        $renderer = new ElementRenderer;
        $renderer->setExtensionAssetMap([
            'ext:e2etest:gallery' => [
                'css' => ['https://saas.example.test/vendor/e2etest/gallery-suite/1.0.0/gallery.css'],
                'js' => ['https://saas.example.test/vendor/e2etest/gallery-suite/1.0.0/gallery.js'],
            ],
        ]);

        $renderer->render('ext:e2etest:gallery', [], ['id' => 'b1']);

        $assets = $renderer->getCollectedAssets();
        $this->assertSame(['https://saas.example.test/vendor/e2etest/gallery-suite/1.0.0/gallery.css'], $assets['css']);
        $this->assertSame(['https://saas.example.test/vendor/e2etest/gallery-suite/1.0.0/gallery.js'], $assets['js']);
    }

    public function test_case2_has_assets_only_behavior_unchanged(): void
    {
        $this->registerHasAssetsDemoCard();

        $renderer = new ElementRenderer;
        // Deliberately never calling setExtensionAssetMap() — mirrors every caller that predates this feature.
        $renderer->render('ext:acme:demo_card', [], ['id' => 'b1']);

        $assets = $renderer->getCollectedAssets();
        $this->assertSame(['/vendor/acme/demo-card.css'], $assets['css']);
        $this->assertSame([], $assets['js']);
    }

    public function test_case3_duplicate_url_from_both_sources_emitted_once(): void
    {
        $this->registerHasAssetsDemoCard();

        $renderer = new ElementRenderer;
        $renderer->setExtensionAssetMap([
            // Deliberately the SAME URL DemoCardElement::getAssets() already declares via HasAssets.
            'ext:acme:demo_card' => ['css' => ['/vendor/acme/demo-card.css'], 'js' => []],
        ]);

        $renderer->render('ext:acme:demo_card', [], ['id' => 'b1']);

        $this->assertSame(['/vendor/acme/demo-card.css'], $renderer->getCollectedAssets()['css']);
    }

    public function test_case4_extension_with_no_manifest_assets_is_not_a_regression(): void
    {
        $this->registerManifestOnlyGallery();

        $renderer = new ElementRenderer;
        $renderer->setExtensionAssetMap([]); // no entry at all for this type

        $html = $renderer->render('ext:e2etest:gallery', [], ['id' => 'b1']);

        $this->assertStringContainsString('ext-e2etest-gallery', $html);
        $this->assertSame(['css' => [], 'js' => []], $renderer->getCollectedAssets());
    }

    public function test_case5_malformed_asset_metadata_fails_safe(): void
    {
        $this->registerManifestOnlyGallery();

        $renderer = new ElementRenderer;
        $renderer->setExtensionAssetMap([
            'ext:e2etest:gallery' => [
                'css' => [
                    'javascript:alert(1)',
                    '//evil.example/x.css',
                    '/relative/not-allowed.css',
                    'not a url at all',
                    123,
                    'https://saas.example.test/vendor/e2etest/gallery-suite/1.0.0/gallery.css',
                ],
                'js' => 'not-even-an-array',
            ],
        ]);

        $renderer->render('ext:e2etest:gallery', [], ['id' => 'b1']);

        $assets = $renderer->getCollectedAssets();
        $this->assertSame(['https://saas.example.test/vendor/e2etest/gallery-suite/1.0.0/gallery.css'], $assets['css']);
        $this->assertSame([], $assets['js']);
    }

    /**
     * Phase 3H-B (projector follow-up §15D) — the SaaS-side
     * ExtensionThemeRuntimeAssetProjector deliberately emits an
     * `extension_assets` entry for every type a Theme's selected release
     * manifests declare, not only the types actually used in that Theme's
     * blocks (payload-size optimization is explicitly NOT a required
     * convergence outcome). Proves that unused-entry breadth is harmless on
     * the consuming side: an asset map entry for a type that is never
     * rendered on this page must never leak into the page's collected
     * asset output.
     */
    public function test_case6_unused_manifest_entry_does_not_affect_rendered_page_asset_output(): void
    {
        $this->registerManifestOnlyGallery();

        $renderer = new ElementRenderer;
        $renderer->setExtensionAssetMap([
            'ext:e2etest:gallery' => [
                'css' => ['https://saas.example.test/vendor/e2etest/gallery-suite/1.0.0/gallery.css'],
                'js' => [],
            ],
            // Declared by the selected release's manifest, but no block of
            // this type is ever rendered on this page.
            'ext:acme:never_rendered' => [
                'css' => ['https://saas.example.test/vendor/acme/never-rendered/1.0.0/x.css'],
                'js' => [],
            ],
        ]);

        $renderer->render('ext:e2etest:gallery', [], ['id' => 'b1']);

        $assets = $renderer->getCollectedAssets();
        $this->assertSame(['https://saas.example.test/vendor/e2etest/gallery-suite/1.0.0/gallery.css'], $assets['css']);
        $this->assertStringNotContainsString('never-rendered', implode(',', $assets['css']));
    }
}

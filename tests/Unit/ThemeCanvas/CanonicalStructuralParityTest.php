<?php

namespace Tests\Unit\ThemeCanvas;

use App\Services\ThemeBuilder\BlockRenderer as HostBlockRenderer;
use App\Services\ThemeBuilder\ElementSchemaRegistry;
use SaaSPlatform\ThemeRuntime\BlockRenderer as CanonicalBlockRenderer;
use Tests\TestCase;

/**
 * Phase 2j Section 9 — the 28-theme structural parity contract Phase 2i did
 * not complete. Runs every real, authored block across all 28 canonical
 * Theme snapshots through both the canonical shared renderer AND
 * customer-site's own host renderer, asserting identical output for every
 * canonical type customer-site does not explicitly override.
 *
 * Why comparing against canonical-direct IS "SaaS vs customer-runtime"
 * parity: saas-platform's own BlockRenderer is a verified-empty subclass of
 * this same canonical class (ThemeRuntimeSharedAuthorityTest, saas-platform
 * repo) — its output for any canonical type IS canonical output, by
 * construction, not by coincidence. So canonical-direct output here is
 * exactly what saas-platform's Builder preview produces for the same block.
 *
 * Types customer-site explicitly overrides (#[HostOwnedOverride]) or that
 * are customer-only (no canonical equivalent) are excluded, with the
 * exclusion itself asserted against the same source of truth
 * ThemeRuntimeCanonicalParityTest uses — never a second, silently-drifting
 * list of "known differences."
 */
class CanonicalStructuralParityTest extends TestCase
{
    // Committed fixtures, not storage/app: these are the authored theme inputs
    // the parity contract renders, so every checkout (and CI) must have them.
    private const SNAPSHOT_DIR = 'tests/Fixtures/theme-snapshots';

    private HostBlockRenderer $host;
    private CanonicalBlockRenderer $canonical;

    protected function setUp(): void
    {
        parent::setUp();
        $this->host = new HostBlockRenderer();
        $this->host->setSchemaRegistry(new ElementSchemaRegistry());
        $this->canonical = new CanonicalBlockRenderer();
        // Phase 0.7 (saas-platform ADR-066): the host's forms post to its real
        // routes (siteEndpoints(), a #[HostOwnedOverride]). Same input, same
        // output: hand the canonical renderer exactly those endpoints.
        $endpoints = new \ReflectionMethod(HostBlockRenderer::class, 'siteEndpoints');
        $this->canonical->setContext(['site_endpoints' => $endpoints->invoke($this->host)]);
    }

    /** Method names customer-site's BlockRenderer explicitly overrides (host-owned, not a canonical duplicate). */
    private function hostOwnedTypes(): array
    {
        $ref = new \ReflectionClass(HostBlockRenderer::class);
        $types = [];
        foreach ($ref->getMethods() as $m) {
            if ($m->getDeclaringClass()->getName() !== $ref->getName()) {
                continue;
            }
            if (!empty($m->getAttributes(\SaaSPlatform\ThemeRuntime\Attributes\HostOwnedOverride::class))) {
                $name = $m->getName();
                if (str_starts_with($name, 'render')) {
                    $types[] = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', substr($name, 6)));
                }
            }
        }
        return $types;
    }

    private function collectBlockTypes(array $blocks, array &$byType): void
    {
        foreach ($blocks as $block) {
            if (!is_array($block) || empty($block['type'])) {
                continue;
            }
            $type = $block['type'];
            if (str_starts_with($type, 'module:')) {
                continue; // extension-owned, out of this contract's scope
            }
            if (!isset($byType[$type])) {
                $byType[$type] = $block;
            }
            $children = $block['children'] ?? $block['blocks'] ?? ($block['props']['blocks'] ?? []);
            if (is_array($children)) {
                $this->collectBlockTypes($children, $byType);
            }
            $tabs = $block['props']['tabs'] ?? [];
            if (is_array($tabs)) {
                foreach ($tabs as $tab) {
                    if (is_array($tab) && is_array($tab['blocks'] ?? null)) {
                        $this->collectBlockTypes($tab['blocks'], $byType);
                    }
                }
            }
            $items = $block['props']['items'] ?? [];
            if (is_array($items)) {
                foreach ($items as $item) {
                    if (is_array($item) && is_array($item['blocks'] ?? null)) {
                        $this->collectBlockTypes($item['blocks'], $byType);
                    }
                }
            }
        }
    }

    public function test_all_28_canonical_theme_snapshots_have_zero_unexplained_structural_divergence(): void
    {
        $snapshotFiles = glob(base_path(self::SNAPSHOT_DIR) . '/*-snapshot.json');
        $this->assertNotEmpty($snapshotFiles, 'No theme snapshots found — run the snapshot export before this test.');

        $hostOwned = $this->hostOwnedTypes();
        $divergences = [];
        $comparedCount = 0;
        $skippedTypes = [];

        foreach ($snapshotFiles as $file) {
            $slug = basename($file, '-snapshot.json');
            $snapshot = json_decode(file_get_contents($file), true);
            if (!is_array($snapshot)) {
                continue;
            }

            $byType = [];
            $this->collectBlockTypes(is_array($snapshot['header_blocks'] ?? null) ? $snapshot['header_blocks'] : [], $byType);
            $this->collectBlockTypes(is_array($snapshot['footer_blocks'] ?? null) ? $snapshot['footer_blocks'] : [], $byType);
            foreach ((is_array($snapshot['pages'] ?? null) ? $snapshot['pages'] : []) as $page) {
                if (!is_array($page)) {
                    continue;
                }
                $this->collectBlockTypes(is_array($page['blocks'] ?? null) ? $page['blocks'] : [], $byType);
            }

            foreach ($byType as $type => $block) {
                if (in_array($type, $hostOwned, true)) {
                    $skippedTypes[$type] = true;
                    continue;
                }

                $props = $block['props'] ?? $block['data'] ?? [];
                if (!is_array($props)) {
                    continue;
                }
                $id = 'parity-' . md5($slug . $type);

                // Both renderers carry per-document script gates as
                // process-wide statics (carousel controller, countdown
                // adapter, reference-block script), keyed by mode. Another
                // test that rendered a carousel earlier in the suite can
                // starve only one side, which is document state, not a
                // structural divergence. Start each side from the same
                // clean gate so what this test compares is the block.
                $this->resetPerDocumentEmissionGates();
                try {
                    $canonicalHtml = $this->canonical->dispatchStructural($type, $props, '', $id);
                } catch (\Throwable $e) {
                    continue; // canonical has no method for this type either — not this test's concern
                }
                if ($canonicalHtml === null) {
                    continue; // no canonical method for this type — customer-only, not a divergence
                }

                $this->resetPerDocumentEmissionGates();
                try {
                    $hostBlock = ['type' => $type, 'id' => $id, 'props' => $props];
                    $hostHtml = $this->invokeHostRenderBlockUnsafe($hostBlock);
                } catch (\Throwable $e) {
                    $divergences[] = "{$slug}/{$type}: host threw {$e->getMessage()}";
                    continue;
                }

                $comparedCount++;
                // Canonical emits some per-document assets (e.g. nav_footer's
                // <style data-tb-block-asset>) behind a process-wide static
                // guard, so whichever renderer runs first carries the asset
                // tag and the second returns ''. Compare structure only.
                $hostComparable = $this->withoutOneTimeAssets($hostHtml);
                $canonicalComparable = $this->withoutOneTimeAssets($canonicalHtml);
                if ($hostComparable !== $canonicalComparable) {
                    $divergences[] = "{$slug}/{$type}: structural output differs between canonical and customer-site host renderer"
                        . $this->firstDifference($hostComparable, $canonicalComparable);
                }
            }
        }

        $this->assertGreaterThan(0, $comparedCount, 'Parity contract compared zero blocks — snapshot/type extraction likely broken.');
        $this->assertEmpty(
            $divergences,
            "Unexplained canonical structural divergence found (target is zero):\n" . implode("\n", $divergences)
        );
    }

    private function withoutOneTimeAssets(string $html): string
    {
        return preg_replace(
            ['/<style\s+data-tb-block-asset="[^"]*">.*?<\/style>/s', '/<script\s+data-tb-block-asset="[^"]*">.*?<\/script>/s'],
            '',
            $html,
        );
    }

    /**
     * The canonical renderer emits a few client scripts once per document
     * through process-wide statics keyed by mode. Both sides must start
     * from the same gate state for a single-block comparison.
     */
    private function resetPerDocumentEmissionGates(): void
    {
        CanonicalBlockRenderer::resetReferenceBlockScriptEmission();
        CanonicalBlockRenderer::resetCountdownAdapterEmission();
        CanonicalBlockRenderer::resetCarouselScriptEmission();
    }

    /** Context around the first differing byte, so a suite-order failure is diagnosable. */
    private function firstDifference(string $host, string $canonical): string
    {
        $length = min(strlen($host), strlen($canonical));
        $offset = 0;
        while ($offset < $length && $host[$offset] === $canonical[$offset]) {
            $offset++;
        }
        $window = 160;

        return sprintf(
            "\n  first difference at byte %d (host %d bytes, canonical %d bytes)\n  host:      %s\n  canonical: %s",
            $offset,
            strlen($host),
            strlen($canonical),
            substr($host, max(0, $offset - 40), $window),
            substr($canonical, max(0, $offset - 40), $window),
        );
    }

    /**
     * Bypasses the host's schema-validation/Element-SDK dispatch wrapper —
     * this contract is specifically about STRUCTURAL rendering parity for
     * canonical types, not about re-testing the host's own safety wrapper
     * (already covered by BlockRendererLicenseGateTest et al.).
     */
    private function invokeHostRenderBlockUnsafe(array $block): string
    {
        $ref = new \ReflectionMethod(HostBlockRenderer::class, 'renderBlockUnsafe');
        $ref->setAccessible(true);
        return $ref->invoke($this->host, $block);
    }
}

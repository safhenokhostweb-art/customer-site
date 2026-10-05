<?php

namespace App\Services\ThemeCanvas;

use App\Services\BuilderLayoutService;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Phase 8 Step 1 — Asset-on-Demand manifest generation.
 *
 * Produces a flattened, deduplicated JSON manifest of exactly the CSS/JS
 * assets the blocks actually present on a page require — no more, no less.
 *
 * Hybrid Registry resolution (ADR-030): a block type's extension assets come
 * from the Theme's `extension_assets` payload, keyed by type. The same type
 * may be canonical (SaaSPlatform\ThemeRuntime\BlockRegistry), host-only
 * (registered locally in the Builder), or an extension identity
 * (ExtensionBlockTypeRegistry). For manifest purposes, the union of all
 * three is the single source of truth for "what assets does type X need" —
 * a type absent from ALL three registries is unknown and yields no assets
 * (drift-gate: fail closed, never load).
 *
 * The service NEVER throws to its caller's context. Any exception during
 * generation returns null — the layout's Safe Default fallback (the historical
 * eager global injection) takes over, so a manifest problem can never strip
 * a live page of its assets.
 */
class AssetManifestGeneratorService
{
    public function __construct(
        private readonly BuilderLayoutService $layout,
        private readonly ThemeAssetLocalizationService $assetLocalizer,
    ) {}

    /**
     * Generate the per-page asset manifest.
     *
     * @param array $themeData  The installed Theme's `active_canvas_theme_data`.
     *   Expected keys: `extension_assets` ({type: {css: url[], js: url[]}}),
     *   `theme_assets` (localized manifest).
     * @param array $pageBlocks The Canvas page content blocks (each `{type, id, props}`).
     *
     * @return array{css: string[], js: string[]}|null  null on any failure
     *   (Safe Default fallback at the layout level).
     */
    public function forCanvasPage(array $themeData, array $pageBlocks): ?array
    {
        try {
            $extensionAssets = $themeData['extension_assets'] ?? [];

            $css = [];
            $js = [];
            $seen = ['css' => [], 'js' => []];

            $localizedManifest = $themeData['theme_assets'] ?? null;

            $types = $this->collectRelevantTypes($pageBlocks, $themeData);

            foreach ($types as $type) {
                $entry = $extensionAssets[$type] ?? null;
                if (! is_array($entry)) {
                    continue;
                }

                $this->mergeAssets($entry['css'] ?? [], 'css', $css, $seen);
                $this->mergeAssets($entry['js'] ?? [], 'js', $js, $seen);
            }

            // Resolve any platform-origin URLs to their verified local copies.
            $css = $this->resolveUrls($css, $localizedManifest);
            $js = $this->resolveUrls($js, $localizedManifest);

            return ['css' => $css, 'js' => $js];
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Walk header, footer, and the page's blocks — collecting the distinct
     * block types that actually appear. Uses the Hybrid Registry strategy:
     * header/footer blocks come from BuilderLayoutService, page blocks come
     * from the caller. Each type is checked against the union of all three
     * registries; types unknown to any are silently skipped (drift-gate).
     */
    private function collectRelevantTypes(array $pageBlocks, array $themeData): array
    {
        $types = [];

        $headerBlocks = $this->layout->getHeaderBlocks(null, $themeData);
        $footerBlocks = $this->layout->getFooterBlocks(null, $themeData);

        $allBlocks = array_merge($headerBlocks, $footerBlocks, $pageBlocks);

        foreach ($allBlocks as $block) {
            if (! is_array($block)) {
                continue;
            }
            $type = $block['type'] ?? null;
            if (! is_string($type) || $type === '') {
                continue;
            }

            if ($this->isKnownType($type)) {
                $types[$type] = true;
            }
        }

        return array_keys($types);
    }

    /**
     * Fail-closed type recognition across the Hybrid Registry.
     * A type is "known" if it exists in ANY of the three registries:
     * canonical, host-local (ThemeCanvas), or extension (ExtensionBlockTypeRegistry).
     */
    private function isKnownType(string $type): bool
    {
        if (str_starts_with($type, 'ext:')) {
            return \App\Services\ThemeCanvas\Extension\ExtensionBlockTypeRegistry::has($type);
        }

        if (\SaaSPlatform\ThemeRuntime\BlockRegistry::isCanonicalType($type)) {
            return true;
        }

        $hostTypes = BlockRegistry::all();

        return array_key_exists($type, $hostTypes);
    }

    /**
     * Merge deduplicated URLs into the manifest bucket.
     */
    private function mergeAssets(array $urls, string $bucket, array &$target, array &$seen): void
    {
        foreach ($urls as $url) {
            if (! is_string($url) || $url === '' || in_array($url, $seen[$bucket], true)) {
                continue;
            }

            $seen[$bucket][] = $url;
            $target[] = $url;
        }
    }

    /**
     * Resolve platform-origin URLs to verified customer-local copies using
     * the Theme asset localizer's fail-closed URL resolver. URLs that do not
     * resolve (unknown to the manifest, or not platform-origin) pass through
     * unchanged — matching the pre-Phase-8 eager-injection semantics.
     */
    private function resolveUrls(array $urls, ?array $localizedManifest): array
    {
        if ($localizedManifest === null || $localizedManifest === []) {
            return $urls;
        }

        $resolved = [];
        foreach ($urls as $url) {
            $local = $this->assetLocalizer->localizedUrlFor($url, $localizedManifest);
            $resolved[] = $local ?? $url;
        }

        return array_values(array_unique($resolved, SORT_REGULAR));
    }
}

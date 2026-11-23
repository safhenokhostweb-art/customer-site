<?php

namespace App\Services\ThemeCanvas;

use App\Models\CustomerMedia;
use App\Services\CustomerMediaService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * The single canonical path that turns a Builder prop value into a safe,
 * renderable URL: either the canonical `{"media_id": "<uuid>"}` shape
 * (resolved through CustomerMediaService, current-site-scoped) or a legacy
 * URL/path string (returned unchanged, transitional compatibility). Every
 * media-capable block prop is resolved here, once, before rendering —
 * individual renderers never perform their own media lookup.
 *
 * TASK 3/3 Phase 6.5E (BLD-32) — request-scoped resolution cache. This
 * class is instantiated once per render (lazily memoized on the owning
 * ThemeCanvasRenderer instance — see that class), so an instance-level
 * array here is naturally request-scoped: never a process-global/static
 * cache, never shared across requests or sites, and safe under a
 * long-running worker. Cache keys are `"{siteId}:{uuid}"`, never the UUID
 * alone — CustomerMediaService::siteId() (throws if unconfigured) is the
 * same site-ownership scope findForCurrentSite() already enforces, so a
 * cached miss/hit can never cross a site boundary even in a hypothetical
 * future multi-site-per-process context. Two ways this cache gets
 * populated:
 *  - preload(): a single bulk `whereIn('uuid', ...)` query for every media
 *    UUID a caller already knows it will need (see collectMediaIds()) —
 *    O(1) queries for a whole page's render, not one per occurrence.
 *  - findCached(): a per-UUID fallback for any resolution that happens
 *    without a prior preload() (e.g. a single ad-hoc resolveValue() call)
 *    — still memoized, so repeats of the same UUID within this instance's
 *    lifetime never re-query, but not O(1) across distinct UUIDs on its
 *    own. Misses are cached too (as null), so a forged/missing/foreign
 *    UUID never triggers more than one failed lookup per request.
 */
class CustomerMediaResolver
{
    /** @var array<string, CustomerMedia|null> keyed by "{siteId}:{uuid}" */
    private array $cache = [];

    /**
     * Canvas overhaul Unit 2a: the media-capable fields of a block type are
     * every `image` control in its registry schema, at any repeater depth
     * (SchemaFieldMap). The hand-kept MEDIA_PROPS / MEDIA_REPEATERS lists
     * this replaces had drifted from the registry (bento_grid named a field
     * that does not exist; 17 of 53 image fields were never resolved).
     */
    public const CONTROL_TYPE = 'image';

    public function __construct(private readonly CustomerMediaService $media)
    {
    }

    /**
     * Resolve every media-capable prop of one block type, nested repeater
     * fields included. Props that are not `image` controls pass through
     * untouched.
     */
    public function resolveBlockProps(string $type, array $props): array
    {
        return SchemaFieldMap::transform($type, $props, self::CONTROL_TYPE, fn (mixed $value) => $this->resolveValue($value));
    }

    /**
     * Resolve one value: canonical `{media_id}` -> a real URL, legacy
     * string -> unchanged, anything missing/deleted/foreign/malformed ->
     * '' (fails safely; callers already render an empty URL as "no image
     * selected" — nothing is ever resurrected or guessed). Unchanged
     * authorization/safety semantics from before Phase 6.5E — only the
     * lookup itself is now cached, never the decision of what counts as
     * "found".
     */
    public function resolveValue(mixed $value): string
    {
        if (is_string($value)) {
            if (str_starts_with($value, '/storage/') || str_starts_with($value, 'storage/')) {
                $found = $this->media->findBySourceUrl($value);
                if ($found) {
                    return $this->media->publicUrl($found);
                }
            }
            return $value;
        }
        if (!is_array($value) || !is_string($value['media_id'] ?? null) || $value['media_id'] === '') {
            return '';
        }

        $media = $this->findCached($value['media_id']);

        return $media ? $this->media->publicUrl($media) : '';
    }

    private function findCached(string $uuid): ?CustomerMedia
    {
        $key = $this->media->siteId() . ':' . $uuid;
        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        try {
            return $this->cache[$key] = $this->media->findForCurrentSite($uuid);
        } catch (ModelNotFoundException) {
            return $this->cache[$key] = null;
        }
    }

    /**
     * Bulk-loads every given media UUID for the current site in ONE query
     * and populates the same cache resolveValue()/findCached() read from —
     * the O(1)-per-render half of BLD-32. A UUID this call doesn't find
     * (forged/missing/foreign) is cached as a negative result too, so it
     * can never trigger a second, per-occurrence fallback query later in
     * the same request. Safe no-op for an empty list.
     *
     * @param string[] $uuids
     */
    public function preload(array $uuids): void
    {
        $uuids = array_values(array_unique(array_filter(
            $uuids,
            static fn ($uuid) => is_string($uuid) && $uuid !== ''
        )));
        if ($uuids === []) {
            return;
        }

        $siteId = $this->media->siteId();
        $found = CustomerMedia::forSite($siteId)->available()->whereIn('uuid', $uuids)->get();

        foreach ($found as $media) {
            $this->cache["{$siteId}:{$media->uuid}"] = $media;
        }
        foreach (array_diff($uuids, $found->pluck('uuid')->all()) as $missing) {
            $this->cache["{$siteId}:{$missing}"] = null;
        }
    }

    /**
     * Walks a block list exactly the way resolveBlockProps()/renderBlock()
     * do (recursing into children/nested containers/tabs/accordion items)
     * but only COLLECTS the `{media_id}` UUIDs referenced — never resolves
     * them — so a caller can preload() the whole list in one query before
     * rendering it. Mirrors ThemeMediaLocalizationService's own block walk
     * (the install-time twin of this read-time walk) rather than
     * maintaining a third, independently-drifting tree-walking
     * implementation.
     *
     * @param array $blocks
     * @return string[] unique media UUIDs referenced anywhere in $blocks
     */
    public function collectMediaIds(array $blocks): array
    {
        $uuids = [];
        $this->walkBlocksForMediaIds($blocks, $uuids);

        return array_values(array_unique($uuids));
    }

    /** @param string[] $uuids */
    private function walkBlocksForMediaIds(array $blocks, array &$uuids): void
    {
        foreach ($blocks as $block) {
            if (!is_array($block)) {
                continue;
            }
            $type = $block['type'] ?? null;
            $props = $block['props'] ?? $block['data'] ?? [];
            if (is_string($type) && is_array($props)) {
                $this->collectFromProps($type, $props, $uuids);
            }

            if (isset($block['children']) && is_array($block['children'])) {
                $this->walkBlocksForMediaIds($block['children'], $uuids);
            }
            if (isset($block['blocks']) && is_array($block['blocks'])) {
                $this->walkBlocksForMediaIds($block['blocks'], $uuids);
            }
            if (is_array($props['tabs'] ?? null)) {
                foreach ($props['tabs'] as $tab) {
                    if (is_array($tab) && is_array($tab['blocks'] ?? null)) {
                        $this->walkBlocksForMediaIds($tab['blocks'], $uuids);
                    }
                }
            }
            if ($type === 'accordion' && is_array($props['items'] ?? null)) {
                foreach ($props['items'] as $item) {
                    if (is_array($item) && is_array($item['blocks'] ?? null)) {
                        $this->walkBlocksForMediaIds($item['blocks'], $uuids);
                    }
                }
            }
        }
    }

    /** @param string[] $uuids */
    private function collectFromProps(string $type, array $props, array &$uuids): void
    {
        foreach (SchemaFieldMap::values($type, $props, self::CONTROL_TYPE) as $value) {
            $this->collectUuid($value, $uuids);
        }
    }

    /** @param string[] $uuids */
    private function collectUuid(mixed $value, array &$uuids): void
    {
        if (is_array($value) && is_string($value['media_id'] ?? null) && $value['media_id'] !== '') {
            $uuids[] = $value['media_id'];
        }
    }
}

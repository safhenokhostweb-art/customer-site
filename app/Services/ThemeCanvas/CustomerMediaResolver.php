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
     * Block type => set of its top-level media-capable prop keys.
     * TASK 3/3 Phase 6.5 (BLD-21): made public so
     * ThemeMediaLocalizationService can walk the SAME canonical inventory
     * at install time (to rewrite a master-theme-hosted URL into a real
     * {media_id}) instead of maintaining a second, independently-drifting
     * list of "which props are images".
     */
    public const MEDIA_PROPS = [
        'hero' => ['background_image'],
        'section' => ['background_image'],
        'image' => ['url'],
        'hero_split' => ['image_url'],
        'stats_row' => ['background_image'],
        'module:motion' => ['fallback_image'],
        'video' => ['url', 'poster'],
        'sleepshiman_header' => ['logo'],
        'sleepshiman_amazing_offers' => ['illustration'],
        'sleepshiman_bedding_products' => ['feature_image'],
        'sleepshiman_blanket_products' => ['feature_image'],
        'sleepshiman_articles' => ['feature_image'],
        'sleepshiman_sleep_banner' => ['image'],
        'sleepshiman_editorial' => ['image'],
    ];

    /** Block type => {repeater prop key, nested media field key, storage shape}. */
    public const MEDIA_REPEATERS = [
        'gallery' => ['prop' => 'items', 'field' => 'url', 'storage' => 'array'],
        // Phase 4B.5: FoodMart hero declares its editable slides through the
        // same generic repeater contract — the canonical {media_id} value a
        // slide stores resolves here at render time, exactly like every
        // other media-capable prop, with no FoodMart-specific code path.
        'foodmart_hero' => ['prop' => 'slides', 'field' => 'image', 'storage' => 'array'],
        'team_cards' => ['prop' => 'members', 'field' => 'image', 'storage' => 'json'],
        'slider' => ['prop' => 'slides', 'field' => 'image', 'storage' => 'array'],
        'bento_grid' => ['prop' => 'items', 'field' => 'image', 'storage' => 'array'],
        // Builder integrity audit (2026-09-20) — MASTER_THEME_MEDIA_
        // LOCALIZATION_GAP coverage fix: these four repeater fields were
        // real, live media props on the current canonical
        // universal-multipurpose-qa product (testimonial avatars, timeline
        // milestone photos, client-logo images, nav mega-menu banners) that
        // were never registered here. Since ThemeMediaLocalizationService
        // walks this SAME canonical list at install time, an unregistered
        // field is never even inspected — its saas-platform-hosted hotlink
        // ships to the customer untouched regardless of origin-trust
        // correctness, and CustomerMediaResolver (this class, at render
        // time) would never resolve a real {media_id} stored in it either.
        // Discovered by regenerating the stale test fixture from the real,
        // current seeder and running it through the real localization
        // service end to end — 21 of 54 real Platform hotlinks in that
        // fixture were silently left un-localized by exactly this gap.
        'testimonials_slider' => ['prop' => 'items', 'field' => 'avatar', 'storage' => 'array'],
        // 'timeline's own BlockRegistry schema persists 'items' as a JSON
        // STRING (its 'storage' => 'json', matching the shared canonical
        // schema's textarea convention), unlike the other repeaters here —
        // must match exactly, or resolveRepeater()/localizeBlockProps()
        // silently replaces the string with a decoded array, corrupting
        // the block's persisted shape (caught by
        // ThemeCanvasPaletteCompatibilityTest's schema-validation sweep).
        'timeline' => ['prop' => 'items', 'field' => 'image', 'storage' => 'json'],
        'client_logos' => ['prop' => 'logos', 'field' => 'image', 'storage' => 'array'],
        'nav_header' => ['prop' => 'nav_links', 'field' => 'mega_banner_image', 'storage' => 'array'],
        'sleepshiman_amazing_offers' => ['prop' => 'items', 'field' => 'image', 'storage' => 'array'],
        'sleepshiman_featured_product' => ['prop' => 'items', 'field' => 'image', 'storage' => 'array'],
        'sleepshiman_mattresses' => ['prop' => 'items', 'field' => 'image', 'storage' => 'array'],
        'sleepshiman_promo_banners' => ['prop' => 'items', 'field' => 'image', 'storage' => 'array'],
        'sleepshiman_bedding_products' => ['prop' => 'items', 'field' => 'image', 'storage' => 'array'],
        'sleepshiman_blanket_products' => ['prop' => 'items', 'field' => 'image', 'storage' => 'array'],
        'sleepshiman_brands' => ['prop' => 'items', 'field' => 'image', 'storage' => 'array'],
        'sleepshiman_published_products' => ['prop' => 'items', 'field' => 'image', 'storage' => 'array'],
        'sleepshiman_topic_cards' => ['prop' => 'items', 'field' => 'image', 'storage' => 'array'],
        'sleepshiman_articles' => ['prop' => 'items', 'field' => 'image', 'storage' => 'array'],
        'sleepshiman_reviews' => ['prop' => 'items', 'field' => 'avatar', 'storage' => 'array'],
        'sleepshiman_guide_cards' => ['prop' => 'items', 'field' => 'image', 'storage' => 'array'],
        'sleepshiman_footer' => ['prop' => 'trust_marks', 'field' => 'image', 'storage' => 'array'],
    ];

    public function __construct(private readonly CustomerMediaService $media)
    {
    }

    /**
     * Resolve every known media-capable prop for one block type. Block
     * types/props outside the known list pass through completely untouched.
     */
    public function resolveBlockProps(string $type, array $props): array
    {
        foreach (self::MEDIA_PROPS[$type] ?? [] as $propKey) {
            if (array_key_exists($propKey, $props)) {
                $props[$propKey] = $this->resolveValue($props[$propKey]);
            }
        }

        if ($repeater = self::MEDIA_REPEATERS[$type] ?? null) {
            $props = $this->resolveRepeater($props, $repeater['prop'], $repeater['field'], $repeater['storage']);
        }

        return $props;
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
        foreach (self::MEDIA_PROPS[$type] ?? [] as $propKey) {
            $this->collectUuid($props[$propKey] ?? null, $uuids);
        }

        if ($repeater = self::MEDIA_REPEATERS[$type] ?? null) {
            $raw = $props[$repeater['prop']] ?? null;
            $items = match (true) {
                is_array($raw) => $raw,
                is_string($raw) && $raw !== '' => json_decode($raw, true) ?? [],
                default => [],
            };
            if (is_array($items)) {
                foreach ($items as $item) {
                    if (is_array($item)) {
                        $this->collectUuid($item[$repeater['field']] ?? null, $uuids);
                    }
                }
            }
        }
    }

    /** @param string[] $uuids */
    private function collectUuid(mixed $value, array &$uuids): void
    {
        if (is_array($value) && is_string($value['media_id'] ?? null) && $value['media_id'] !== '') {
            $uuids[] = $value['media_id'];
        }
    }

    private function resolveRepeater(array $props, string $propKey, string $field, string $storage): array
    {
        $raw = $props[$propKey] ?? null;
        $items = match (true) {
            is_array($raw) => $raw,
            is_string($raw) && $raw !== '' => json_decode($raw, true) ?? [],
            default => [],
        };
        if (!is_array($items)) {
            return $props;
        }

        foreach ($items as $index => $item) {
            if (is_array($item) && array_key_exists($field, $item)) {
                $items[$index][$field] = $this->resolveValue($item[$field]);
            }
        }

        $props[$propKey] = $storage === 'json' ? json_encode($items, JSON_UNESCAPED_UNICODE) : $items;

        return $props;
    }
}

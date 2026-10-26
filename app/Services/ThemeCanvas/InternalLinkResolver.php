<?php

namespace App\Services\ThemeCanvas;

use App\Models\Page;

/**
 * TASK 3/3 Phase 6.5C (BLD-28) — the single canonical place that turns a
 * Builder link-capable prop value into a safe, renderable URL: either the
 * canonical `{"type":"internal_page","page_id":N}` shape (resolved to that
 * Page's CURRENT slug at render time — the same guarantee
 * MenuItem::resolveUrl() already gives Menu items) or a legacy plain URL
 * string (returned unchanged, exactly like every existing custom link).
 * Mirrors CustomerMediaResolver's own {media_id} pattern deliberately, so
 * this is a second instance of one established convention, not a new one.
 *
 * A Page that is unpublished, deleted, or was never real resolves to ''
 * (the same "no link" contract every renderer here already understands
 * for an empty url prop) — never a guessed/fabricated URL.
 */
class InternalLinkResolver
{
    /** Block type => set of its top-level internal-link-capable prop keys. */
    public const LINK_PROPS = [
        'button' => ['url'],
        'cta' => ['button_url'],
        'hero' => ['button_url'],
        'hero_split' => ['button_url', 'button2_url'],
        'hero_gradient' => ['button_url', 'button2_url'],
        'hero_trust' => ['btn_primary_url', 'btn_secondary_url'],
    ];

    /**
     * Resolve every known link-capable prop for one block type, plus any
     * other top-level prop holding an internal-page reference. The Canvas
     * editor offers the page picker on every top-level `link` field
     * (Phase 0.2b, ADR-061: header/footer/promo/about links among them),
     * and the {type: internal_page} shape is written only by that picker —
     * so resolving by shape keeps every such field working without an
     * allowlist that has to grow with the registry.
     */
    public function resolveBlockProps(string $type, array $props): array
    {
        $known = self::LINK_PROPS[$type] ?? [];
        foreach ($props as $propKey => $value) {
            if (in_array($propKey, $known, true) || self::isInternalPageReference($value)) {
                $props[$propKey] = $this->resolveValue($value);
            }
        }

        return $props;
    }

    private static function isInternalPageReference(mixed $value): bool
    {
        return is_array($value) && ($value['type'] ?? null) === 'internal_page';
    }

    public function resolveValue(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }
        if (!is_array($value) || ($value['type'] ?? null) !== 'internal_page') {
            return '';
        }

        $pageId = $value['page_id'] ?? null;
        if (!is_int($pageId) && !(is_string($pageId) && ctype_digit($pageId))) {
            return '';
        }

        $page = Page::query()->where('is_published', true)->find((int) $pageId);

        return $page ? route('page.show', $page->slug) : '';
    }
}

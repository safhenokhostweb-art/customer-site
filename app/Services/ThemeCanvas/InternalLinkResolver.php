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

    /** Resolve every known link-capable prop for one block type. */
    public function resolveBlockProps(string $type, array $props): array
    {
        foreach (self::LINK_PROPS[$type] ?? [] as $propKey) {
            if (array_key_exists($propKey, $props)) {
                $props[$propKey] = $this->resolveValue($props[$propKey]);
            }
        }

        return $props;
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

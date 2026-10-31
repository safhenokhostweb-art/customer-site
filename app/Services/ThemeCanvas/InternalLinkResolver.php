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
    /**
     * Resolve every internal-page reference of one block: any top-level
     * prop holding one, plus every `link` control nested in a repeater
     * (nav links, footer columns, card links — SchemaFieldMap). The
     * {type: internal_page} shape is written only by the page picker, so
     * resolving by shape needs no per-type allowlist.
     *
     * Canvas overhaul Unit 2b: only references are touched. The former
     * LINK_PROPS allowlist also ran resolveValue() on every other value of
     * its fields, which turned an ADR-061 structured {url, target} link on
     * a hero/CTA button into '' (no link).
     */
    public function resolveBlockProps(string $type, array $props): array
    {
        $resolve = fn (mixed $value) => self::isInternalPageReference($value) ? $this->resolveValue($value) : $value;

        foreach ($props as $propKey => $value) {
            $props[$propKey] = $resolve($value);
        }

        return SchemaFieldMap::transform($type, $props, 'link', $resolve);
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

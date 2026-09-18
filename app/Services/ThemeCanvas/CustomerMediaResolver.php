<?php

namespace App\Services\ThemeCanvas;

use App\Services\CustomerMediaService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * The single canonical path that turns a Builder prop value into a safe,
 * renderable URL: either the canonical `{"media_id": "<uuid>"}` shape
 * (resolved through CustomerMediaService, current-site-scoped) or a legacy
 * URL/path string (returned unchanged, transitional compatibility). Every
 * media-capable block prop is resolved here, once, before rendering —
 * individual renderers never perform their own media lookup.
 */
class CustomerMediaResolver
{
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
        'module:motion' => ['fallback_image'],
        'video' => ['url', 'poster'],
    ];

    /** Block type => {repeater prop key, nested media field key, storage shape}. */
    public const MEDIA_REPEATERS = [
        'gallery' => ['prop' => 'items', 'field' => 'url', 'storage' => 'array'],
        'team_cards' => ['prop' => 'members', 'field' => 'image', 'storage' => 'json'],
        'slider' => ['prop' => 'slides', 'field' => 'image', 'storage' => 'array'],
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
     * selected" — nothing is ever resurrected or guessed).
     */
    public function resolveValue(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }
        if (!is_array($value) || !is_string($value['media_id'] ?? null) || $value['media_id'] === '') {
            return '';
        }

        try {
            $media = $this->media->findForCurrentSite($value['media_id']);
            return $this->media->publicUrl($media);
        } catch (ModelNotFoundException) {
            return '';
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

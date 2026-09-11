<?php

namespace App\Builder\Elements;

class HeroElement extends BaseElement
{
    public static function getType(): string { return 'hero'; }
    public static function getName(): string { return 'بخش قهرمان'; }
    public static function getIcon(): string { return '🎯'; }
    public static function getCategory(): string { return 'layout'; }
    public static function getDefaultProps(): array
    {
        return ['title' => '', 'subtitle' => '', 'button_text' => '', 'button_url' => '#', 'background' => 'primary', 'background_image' => '', 'align' => 'center'];
    }
    public static function getSchema(): array
    {
        return [
            'title' => ['type' => 'string', 'required' => false, 'default' => '', 'max' => 500],
            'subtitle' => ['type' => 'string', 'required' => false, 'default' => '', 'max' => 1000],
            'button_text' => ['type' => 'string', 'required' => false, 'default' => '', 'max' => 100],
            'button_url' => ['type' => 'string', 'required' => false, 'default' => '#', 'max' => 2048],
            'background' => ['type' => 'string', 'required' => false, 'default' => 'primary', 'max' => 50],
            // MEDIA-4: already resolved to a plain URL string by
            // CustomerMediaResolver before this element ever sees it
            // (ThemeCanvasRenderer::renderBlock() runs first) — declared
            // here only so ElementValidator::validate() (which rebuilds
            // props from exactly this schema's keys) stops silently
            // dropping it.
            'background_image' => ['type' => 'string', 'required' => false, 'default' => '', 'max' => 2048, 'control' => 'media'],
            'align' => ['type' => 'enum', 'values' => ['left', 'center', 'right'], 'default' => 'center'],
        ];
    }
    /**
     * Phase 2j (ADR-030/031): delegates to the shared canonical authority,
     * which now also validates button_url via the shared escapeUrl()
     * (promoted from this element's own isValidUrl() check) — canonical is
     * no longer less safe than this element was.
     *
     * MEDIA-4: neither the shared authority's renderHero() nor this
     * element's own $fallback applied `background_image` as CSS — a media
     * control the Canvas picker exposed with no runtime effect, found
     * during the media-architecture audit. By the time this reaches here,
     * `background_image` has already been resolved by
     * CustomerMediaResolver (ThemeCanvasRenderer::renderBlock()) to a
     * plain URL string — this only ever injects an already-safe, already-
     * resolved value, never performs its own media lookup.
     */
    public static function render(array $props, array $context = []): string
    {
        $title = self::escape($props['title'] ?? '');
        $subtitle = self::escape($props['subtitle'] ?? '');
        $btnText = self::escape($props['button_text'] ?? '');
        $btnUrl = $props['button_url'] ?? '#';
        if (!self::isValidUrl($btnUrl)) $btnUrl = '#';
        $btnUrl = self::escapeAttr($btnUrl);
        $bg = $props['background'] ?? 'primary';
        $align = in_array($props['align'] ?? 'center', ['left', 'center', 'right'], true) ? $props['align'] : 'center';
        $id = $context['id'] ?? '';
        $btn = $btnText ? "<a href=\"{$btnUrl}\" class=\"tb-btn tb-btn-primary\">{$btnText}</a>" : '';
        $fallback = "<section id=\"tb-{$id}\" class=\"tb-hero tb-bg-{$bg} tb-align-{$align}\"><div class=\"tb-hero-inner\"><h1 class=\"tb-hero-title\">{$title}</h1><p class=\"tb-hero-subtitle\">{$subtitle}</p>{$btn}</div></section>";
        $html = app(\SaaSPlatform\ThemeRuntime\BlockRenderer::class)
            ->dispatchStructural('hero', $props, $context['inner'] ?? '', $id)
            ?? $fallback;

        $bgImage = trim((string) ($props['background_image'] ?? ''));
        if ($bgImage === '') {
            return $html;
        }
        $style = 'background-image:url(' . self::escapeAttr($bgImage) . ');background-size:cover;background-position:center;';
        $needle = "class=\"tb-hero tb-bg-{$bg} tb-align-{$align}\"";
        if (!str_contains($html, $needle)) {
            return $html;
        }
        return str_replace($needle, $needle . ' style="' . $style . '"', $html);
    }
}

<?php

namespace App\Builder\Elements;

class ImageElement extends BaseElement
{
    public static function getType(): string { return 'image'; }
    public static function getName(): string { return 'تصویر'; }
    public static function getIcon(): string { return '🖼️'; }
    public static function getCategory(): string { return 'content'; }
    public static function getDefaultProps(): array { return ['url' => '', 'alt' => '', 'caption' => '']; }
    public static function getSchema(): array
    {
        return [
            'url' => ['type' => 'string', 'required' => false, 'default' => '', 'max' => 2048],
            'alt' => ['type' => 'string', 'required' => false, 'default' => '', 'max' => 500],
            'caption' => ['type' => 'string', 'required' => false, 'default' => '', 'max' => 500],
        ];
    }
    /** Phase 2j (ADR-030/031): delegates to the shared canonical authority. */
    public static function render(array $props, array $context = []): string
    {
        $id = $context['id'] ?? '';
        $url = (string) ($props['url'] ?? '');
        $fallback = "<figure id=\"tb-{$id}\" class=\"tb-image tb-image-placeholder\"><span>تصویر انتخاب نشده</span></figure>";
        if ($url === '' || !self::isValidUrl($url)) {
            return $fallback;
        }
        return app(\SaaSPlatform\ThemeRuntime\BlockRenderer::class)
            ->dispatchStructural('image', $props, $context['inner'] ?? '', $id)
            ?? $fallback;
    }
}

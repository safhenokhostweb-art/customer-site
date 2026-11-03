<?php

namespace App\Builder\Elements;

class ButtonElement extends BaseElement
{
    public static function getType(): string { return 'button'; }
    public static function getName(): string { return 'دکمه'; }
    public static function getIcon(): string { return '🔘'; }
    public static function getCategory(): string { return 'content'; }
    public static function getDefaultProps(): array { return ['text' => 'دکمه', 'url' => '#', 'style' => 'primary']; }
    public static function getSchema(): array
    {
        return [
            'text' => ['type' => 'string', 'required' => false, 'default' => 'دکمه', 'max' => 200],
            'url' => ['type' => 'link', 'required' => false, 'default' => '#', 'control' => 'url'],
            'style' => ['type' => 'enum', 'values' => ['primary', 'secondary'], 'default' => 'primary'],
        ];
    }
    /** Phase 2j (ADR-030/031): delegates to the shared canonical authority. */
    public static function render(array $props, array $context = []): string
    {
        $id = $context['id'] ?? '';
        $text = self::escape((string) ($props['text'] ?? ''));
        $url = self::escapeAttr(self::linkUrl($props['url'] ?? '#'));
        $style = in_array($props['style'] ?? 'primary', ['primary', 'secondary'], true) ? $props['style'] : 'primary';
        return app(\SaaSPlatform\ThemeRuntime\BlockRenderer::class)
            ->dispatchStructural('button', $props, $context['inner'] ?? '', $id)
            ?? "<a id=\"tb-{$id}\" href=\"{$url}\" class=\"tb-btn tb-btn-{$style}\">{$text}</a>";
    }
}

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
            'url' => ['type' => 'string', 'required' => false, 'default' => '#', 'max' => 2048],
            'style' => ['type' => 'enum', 'values' => ['primary', 'secondary'], 'default' => 'primary'],
        ];
    }
    public static function render(array $props, array $context = []): string
    {
        $text = self::escape((string) ($props['text'] ?? ''));
        $url = (string) ($props['url'] ?? '#');
        if (!self::isValidUrl($url)) $url = '#';
        $url = self::escapeAttr($url);
        $style = in_array($props['style'] ?? 'primary', ['primary', 'secondary'], true) ? $props['style'] : 'primary';
        $id = $context['id'] ?? '';
        return "<a id=\"tb-{$id}\" href=\"{$url}\" class=\"tb-btn tb-btn-{$style}\">{$text}</a>";
    }
}

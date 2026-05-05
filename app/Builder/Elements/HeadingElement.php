<?php

namespace App\Builder\Elements;

class HeadingElement extends BaseElement
{
    public static function getType(): string { return 'heading'; }
    public static function getName(): string { return 'عنوان'; }
    public static function getIcon(): string { return '📝'; }
    public static function getCategory(): string { return 'content'; }
    public static function getDefaultProps(): array { return ['level' => 2, 'text' => 'عنوان']; }
    public static function getSchema(): array
    {
        return [
            'text' => ['type' => 'string', 'required' => false, 'default' => '', 'max' => 2000],
            'level' => ['type' => 'enum', 'values' => [1, 2, 3, 4, 5, 6], 'default' => 2],
        ];
    }
    public static function render(array $props, array $context = []): string
    {
        $level = max(1, min(6, (int)($props['level'] ?? 2)));
        $text = self::escape((string)($props['text'] ?? ''));
        $id = $context['id'] ?? '';
        $style = $props['_style']['typography'] ?? [];
        $inline = [];
        if (!empty($style['color'])) $inline[] = 'color:' . self::escapeAttr($style['color']);
        if (!empty($style['textAlign']) && in_array($style['textAlign'], ['left', 'right', 'center'], true)) $inline[] = 'text-align:' . self::escapeAttr($style['textAlign']);
        $inline[] = 'display:block'; $inline[] = 'width:100%';
        $styleAttr = $inline ? ' style="' . implode(';', $inline) . '"' : '';
        return "<h{$level} id=\"tb-{$id}\" class=\"tb-heading\"{$styleAttr}>{$text}</h{$level}>";
    }
}

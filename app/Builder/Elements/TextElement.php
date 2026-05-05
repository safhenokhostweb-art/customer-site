<?php

namespace App\Builder\Elements;

class TextElement extends BaseElement
{
    public static function getType(): string { return 'text'; }
    public static function getName(): string { return 'متن'; }
    public static function getIcon(): string { return '📄'; }
    public static function getCategory(): string { return 'content'; }
    public static function getDefaultProps(): array { return ['content' => 'متن خود را اینجا بنویسید.']; }
    public static function getSchema(): array
    {
        return ['content' => ['type' => 'string', 'required' => false, 'default' => '', 'max' => 65535]];
    }
    public static function render(array $props, array $context = []): string
    {
        $content = self::sanitizeHtml((string) ($props['content'] ?? ''));
        $id = $context['id'] ?? '';
        $style = $props['_style']['typography'] ?? [];
        $w = []; $i = [];
        if (!empty($style['color'])) $w[] = 'color:' . self::escapeAttr($style['color']);
        if (!empty($style['textAlign']) && in_array($style['textAlign'], ['left', 'right', 'center'], true)) {
            $w[] = 'text-align:' . self::escapeAttr($style['textAlign']);
            $i[] = 'text-align:' . self::escapeAttr($style['textAlign']);
        }
        $wa = $w ? ' style="' . implode(';', $w) . '"' : '';
        $ia = $i ? ' style="' . implode(';', $i) . '"' : '';
        return "<div id=\"tb-{$id}\" class=\"tb-text\"{$wa}><div{$ia}>{$content}</div></div>";
    }
}

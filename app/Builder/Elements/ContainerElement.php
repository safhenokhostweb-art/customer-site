<?php

namespace App\Builder\Elements;

class ContainerElement extends BaseElement
{
    public static function getType(): string { return 'container'; }
    public static function getName(): string { return 'کانتینر'; }
    public static function getIcon(): string { return '▦'; }
    public static function getCategory(): string { return 'layout'; }
    public static function getDefaultProps(): array { return ['maxWidth' => '7xl']; }
    public static function getSchema(): array { return ['maxWidth' => ['type' => 'string', 'required' => false, 'default' => '7xl', 'max' => 20]]; }
    public static function render(array $props, array $context = []): string
    {
        $max = self::escapeAttr($props['maxWidth'] ?? '7xl'); $inner = $context['inner'] ?? ''; $id = $context['id'] ?? '';
        return "<div id=\"tb-{$id}\" class=\"tb-container tb-max-{$max}\">{$inner}</div>";
    }
}

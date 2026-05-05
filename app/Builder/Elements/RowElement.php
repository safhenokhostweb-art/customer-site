<?php

namespace App\Builder\Elements;

class RowElement extends BaseElement
{
    public static function getType(): string { return 'row'; }
    public static function getName(): string { return 'ردیف'; }
    public static function getIcon(): string { return '⇄'; }
    public static function getCategory(): string { return 'layout'; }
    public static function getDefaultProps(): array { return ['gap' => 'md']; }
    public static function getSchema(): array
    {
        return ['gap' => ['type' => 'enum', 'values' => ['none', 'xs', 'sm', 'md', 'lg', 'xl'], 'default' => 'md']];
    }
    public static function render(array $props, array $context = []): string
    {
        $gap = self::spacingClass($props['gap'] ?? 'md');
        $inner = $context['inner'] ?? '';
        $id = $context['id'] ?? '';
        return "<div id=\"tb-{$id}\" class=\"tb-row tb-gap-{$gap}\">{$inner}</div>";
    }
}

<?php

namespace App\Builder\Elements;

class GridElement extends BaseElement
{
    public static function getType(): string { return 'grid'; }
    public static function getName(): string { return 'گرید'; }
    public static function getIcon(): string { return '⊞'; }
    public static function getCategory(): string { return 'layout'; }
    public static function getDefaultProps(): array { return ['columns' => 3, 'gap' => 'md']; }
    public static function getSchema(): array
    {
        return [
            'columns' => ['type' => 'integer', 'required' => false, 'default' => 3],
            'gap' => ['type' => 'enum', 'values' => ['none', 'xs', 'sm', 'md', 'lg', 'xl'], 'default' => 'md'],
        ];
    }
    public static function render(array $props, array $context = []): string
    {
        $cols = max(1, min(6, (int)($props['columns'] ?? 3)));
        $gap = self::spacingClass($props['gap'] ?? 'md');
        $inner = $context['inner'] ?? ''; $id = $context['id'] ?? '';
        return "<div id=\"tb-{$id}\" class=\"tb-grid tb-grid-cols-{$cols} tb-gap-{$gap}\">{$inner}</div>";
    }
}

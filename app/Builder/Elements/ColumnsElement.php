<?php

namespace App\Builder\Elements;

class ColumnsElement extends BaseElement
{
    public static function getType(): string { return 'columns'; }
    public static function getName(): string { return 'ستون‌ها'; }
    public static function getIcon(): string { return '▥'; }
    public static function getCategory(): string { return 'layout'; }
    public static function getDefaultProps(): array { return ['ratios' => '1/2,1/2', 'gap' => 'md']; }
    public static function getSchema(): array
    {
        return [
            'ratios' => ['type' => 'string', 'required' => false, 'default' => '1/2,1/2', 'max' => 100],
            'gap' => ['type' => 'enum', 'values' => ['none', 'xs', 'sm', 'md', 'lg', 'xl'], 'default' => 'md'],
        ];
    }
    public static function render(array $props, array $context = []): string
    {
        $parts = array_map('trim', explode(',', $props['ratios'] ?? '1/2,1/2'));
        $classes = [];
        foreach ($parts as $i => $r) { $classes[] = 'tb-col-' . ($i + 1); }
        $colClass = implode(' ', $classes);
        $inner = $context['inner'] ?? '';
        $id = $context['id'] ?? '';
        return "<div id=\"tb-{$id}\" class=\"tb-columns {$colClass}\">{$inner}</div>";
    }
}

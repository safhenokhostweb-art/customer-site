<?php

namespace App\Builder\Elements;

class ColumnElement extends BaseElement
{
    public static function getType(): string { return 'column'; }
    public static function getName(): string { return 'ستون'; }
    public static function getIcon(): string { return '▤'; }
    public static function getCategory(): string { return 'layout'; }
    public static function getDefaultProps(): array { return ['width' => '1/2']; }
    public static function getSchema(): array
    {
        return ['width' => ['type' => 'string', 'required' => false, 'default' => '1/1', 'max' => 20]];
    }
    public static function render(array $props, array $context = []): string
    {
        $width = $props['width'] ?? '1/1';
        $widthClass = 'tb-col-full';
        if (preg_match('#^(\d+)/(\d+)$#', $width, $m)) {
            $w = (int) $m[1];
            $t = (int) $m[2];
            if ($t > 0 && $w > 0 && $w <= $t) {
                $widthClass = "tb-col-{$w}-{$t}";
            }
        }
        $inner = $context['inner'] ?? '';
        $id = $context['id'] ?? '';
        return "<div id=\"tb-{$id}\" class=\"tb-column {$widthClass}\">{$inner}</div>";
    }
}

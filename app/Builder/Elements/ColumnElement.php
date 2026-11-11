<?php

namespace App\Builder\Elements;

use App\Builder\Contracts\HasContainerCapability;

class ColumnElement extends BaseElement implements HasContainerCapability
{
    public static function getType(): string { return 'column'; }
    public static function getName(): string { return 'ستون'; }
    public static function getIcon(): string { return '▤'; }
    public static function getCategory(): string { return 'layout'; }
    public static function getDefaultProps(): array { return ['width' => '1/1']; }
    public static function getSchema(): array
    {
        return ['width' => ['type' => 'string', 'required' => false, 'default' => '1/1', 'max' => 20]];
    }

    /**
     * A real multi-child container: a `column` holds any number of blocks
     * stacked vertically, and may itself hold another `columns` row (nested
     * columns) — hence no child-type restriction. Each column is identified
     * by its own block id, so per-column styling and width controls never
     * leak onto a sibling or the parent row.
     */
    public static function isContainer(): bool { return true; }
    public static function getAllowedChildren(): ?array { return null; }
    public static function render(array $props, array $context = []): string
    {
        $width = $props['width'] ?? '1/1';
        $widthClass = 'tb-col-full';
        $inline = '';
        if (is_numeric($width)) {
            // Numeric width (percent) is the Canvas-authored form: express it
            // inline so it works both as a flex item in a `row` and as a grid
            // cell in a `columns` row.
            $percent = max(1.0, min(100.0, (float) $width));
            $percent = rtrim(rtrim(number_format($percent, 3, '.', ''), '0'), '.');
            $widthClass = '';
            $inline = " style=\"flex:0 0 {$percent}%;width:{$percent}%;\"";
        } elseif (preg_match('#^(\d+)/(\d+)$#', $width, $m)) {
            $w = (int) $m[1];
            $t = (int) $m[2];
            if ($t > 0 && $w > 0 && $w <= $t) {
                $widthClass = $w === $t ? 'tb-col-full' : "tb-col-{$w}-{$t}";
            }
        }
        $inner = $context['inner'] ?? '';
        $id = $context['id'] ?? '';
        $classes = trim("tb-column {$widthClass}");
        return "<div id=\"tb-{$id}\" class=\"{$classes}\"{$inline}>{$inner}</div>";
    }
}

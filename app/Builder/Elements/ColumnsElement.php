<?php

namespace App\Builder\Elements;

use App\Builder\Contracts\HasContainerCapability;

class ColumnsElement extends BaseElement implements HasContainerCapability
{
    public static function getType(): string { return 'columns'; }
    public static function getName(): string { return 'ستون‌ها'; }
    public static function getIcon(): string { return '▥'; }
    public static function getCategory(): string { return 'layout'; }
    public static function getDefaultProps(): array { return ['ratios' => '1/2,1/2', 'gap' => 'md', 'columns' => []]; }
    public static function getSchema(): array
    {
        return [
            'ratios' => ['type' => 'string', 'required' => false, 'default' => '1/2,1/2', 'max' => 100],
            'gap' => ['type' => 'enum', 'values' => ['none', 'xs', 'sm', 'md', 'lg', 'xl'], 'default' => 'md'],
            // Canvas-authored per-column widths (`[{"width": 35}, …]`, percent).
            // ElementValidator rebuilds props from THIS schema, so without this
            // key every width written by the inspector or a Canvas drag was
            // stripped before render and the row fell back to `ratios` — both
            // on the public page and in the live preview. Declared here so the
            // value survives validation; the canonical renderer clamps each
            // entry to 10–90%.
            'columns' => ['type' => 'array', 'required' => false, 'default' => []],
        ];
    }

    /** Phase 3E-D: generic wrapper — no child-type restriction. */
    public static function isContainer(): bool { return true; }
    public static function getAllowedChildren(): ?array { return null; }
    /** Phase 2j (ADR-030/031): delegates to the shared canonical authority. */
    public static function render(array $props, array $context = []): string
    {
        $inner = $context['inner'] ?? '';
        $id = $context['id'] ?? '';
        $parts = array_map('trim', explode(',', $props['ratios'] ?? '1/2,1/2'));
        $classes = [];
        foreach ($parts as $i => $r) { $classes[] = 'tb-col-' . ($i + 1); }
        $colClass = implode(' ', $classes);
        // editor_mode must reach the canonical renderer so it emits the
        // resize handles in the Canvas; the class is resolved per request and
        // otherwise renders public output (no handles) byte-identically.
        $renderer = app(\SaaSPlatform\ThemeRuntime\BlockRenderer::class);
        $renderer->setContext([
            'editor_mode' => (bool) ($context['editor_mode'] ?? false),
            'mode' => $context['mode'] ?? null,
        ]);
        return $renderer->dispatchStructural('columns', $props, $inner, $id)
            ?? "<div id=\"tb-{$id}\" class=\"tb-columns {$colClass}\">{$inner}</div>";
    }
}

<?php

namespace App\Builder\Elements;

use App\Builder\Contracts\HasContainerCapability;

class GridElement extends BaseElement implements HasContainerCapability
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

    /** Phase 3E-D: generic wrapper — no child-type restriction. */
    public static function isContainer(): bool { return true; }
    public static function getAllowedChildren(): ?array { return null; }
    /** Phase 2j (ADR-030/031): delegates to the shared canonical authority. */
    public static function render(array $props, array $context = []): string
    {
        $inner = $context['inner'] ?? ''; $id = $context['id'] ?? '';
        $cols = max(1, min(6, (int)($props['columns'] ?? 3)));
        $gap = self::spacingClass($props['gap'] ?? 'md');
        return app(\SaaSPlatform\ThemeRuntime\BlockRenderer::class)
            ->dispatchStructural('grid', $props, $inner, $id)
            ?? "<div id=\"tb-{$id}\" class=\"tb-grid tb-grid-cols-{$cols} tb-gap-{$gap}\">{$inner}</div>";
    }
}

<?php

namespace App\Builder\Elements;

/**
 * Horizontal divider.
 */
class DividerElement extends BaseElement
{
    public static function getType(): string
    {
        return 'divider';
    }

    public static function getName(): string
    {
        return 'جداکننده';
    }

    public static function getIcon(): string
    {
        return '➖';
    }

    public static function getCategory(): string
    {
        return 'content';
    }

    public static function getDefaultProps(): array
    {
        return [];
    }

    public static function getSchema(): array
    {
        return [];
    }

    public static function render(array $props, array $context = []): string
    {
        $id = $context['id'] ?? '';
        return "<hr id=\"tb-{$id}\" class=\"tb-divider\">";
    }
}

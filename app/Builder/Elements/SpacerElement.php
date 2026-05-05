<?php

namespace App\Builder\Elements;

/**
 * Vertical spacer (height).
 */
class SpacerElement extends BaseElement
{
    public static function getType(): string
    {
        return 'spacer';
    }

    public static function getName(): string
    {
        return 'فاصله';
    }

    public static function getIcon(): string
    {
        return '↕️';
    }

    public static function getCategory(): string
    {
        return 'content';
    }

    public static function getDefaultProps(): array
    {
        return ['height' => 'md'];
    }

    public static function getSchema(): array
    {
        return [
            'height' => ['type' => 'enum', 'values' => ['none', 'xs', 'sm', 'md', 'lg', 'xl'], 'default' => 'md'],
        ];
    }

    public static function render(array $props, array $context = []): string
    {
        $height = $props['height'] ?? 'md';
        $map = ['none' => '0', 'xs' => '0.5rem', 'sm' => '1rem', 'md' => '1.5rem', 'lg' => '2.5rem', 'xl' => '4rem'];
        $h = $map[$height] ?? '1.5rem';
        $id = $context['id'] ?? '';
        return "<div id=\"tb-{$id}\" class=\"tb-spacer\" style=\"height:{$h}\" aria-hidden=\"true\"></div>";
    }
}

<?php

namespace App\Builder\Contracts;

/**
 * Contract for every page builder element.
 * All elements must be self-contained and implement this interface.
 */
interface ElementInterface
{
    public static function getType(): string;

    public static function getName(): string;

    public static function getIcon(): string;

    public static function getCategory(): string;

    public static function getDefaultProps(): array;

    /**
     * Property schema: [ propName => [ 'type' => string|enum|integer|boolean|array, 'required' => bool, 'default' => mixed, 'max' => int?, 'values' => array? ] ]
     */
    public static function getSchema(): array;

    /**
     * Render element HTML. Context may contain: id, inner (for containers), style, menu_html, social_links, etc.
     */
    public static function render(array $props, array $context = []): string;
}

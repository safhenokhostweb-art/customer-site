<?php

namespace App\Builder\Elements;

use App\Builder\Contracts\ElementInterface;
use App\Builder\Traits\WithDefaultProps;
use App\Builder\Traits\WithSanitization;

/**
 * Base class for all page builder elements.
 * Every element is self-contained: type, schema, defaults, render.
 */
abstract class BaseElement implements ElementInterface
{
    use WithDefaultProps;
    use WithSanitization;

    /**
     * Optional: custom validation. Default none.
     * @return string[]
     */
    public static function validate(array $props): array
    {
        return [];
    }

    /**
     * Optional: required assets. Default none.
     * @return array{ css?: string[], js?: string[] }
     */
    public static function getAssets(): array
    {
        return [];
    }

    /**
     * Optional: supports dynamic tokens like {{site.name}}.
     */
    public static function supportsDynamicData(): bool
    {
        return false;
    }

    /**
     * Optional: migrate old props to new shape.
     */
    public static function migrate(array $oldProps): array
    {
        return $oldProps;
    }

    /**
     * Editor metadata: default width/size.
     */
    public static function getDefaultSize(): string
    {
        return 'full';
    }

    /**
     * Required module slug; null = always available.
     */
    public static function getRequiredModule(): ?string
    {
        return null;
    }

    protected static function spacingClass(string $v): string
    {
        $map = ['none' => 'none', 'xs' => 'xs', 'sm' => 'sm', 'md' => 'md', 'lg' => 'lg', 'xl' => 'xl'];
        return $map[$v] ?? 'md';
    }
}

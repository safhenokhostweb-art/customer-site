<?php

namespace App\Builder\Traits;

trait WithDefaultProps
{
    /**
     * Merge props with element defaults. Unknown keys are not stripped here (ElementValidator does that).
     */
    public static function applyDefaults(array $props): array
    {
        return array_merge(static::getDefaultProps(), $props);
    }
}

<?php

namespace App\Builder\Contracts;

/**
 * Optional: element declares required CSS/JS assets.
 * Assets are loaded once per page by the renderer.
 */
interface HasAssets
{
    /**
     * @return array{ css?: string[], js?: string[] }
     */
    public static function getAssets(): array;
}

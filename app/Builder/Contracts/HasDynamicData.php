<?php

namespace App\Builder\Contracts;

/**
 * Optional: element supports dynamic data tokens (e.g. {{site.name}}, {{page.title}}).
 */
interface HasDynamicData
{
    public static function supportsDynamicData(): bool;
}

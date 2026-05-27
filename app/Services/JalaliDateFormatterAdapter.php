<?php

namespace App\Services;

use App\Helpers\Jalali;
use SaaSPlatform\CoreUtils\Contracts\Services\DateFormatterInterface;

/**
 * Bridges DateFormatterInterface to the local static Jalali helper.
 */
class JalaliDateFormatterAdapter implements DateFormatterInterface
{
    public function format(mixed $date, string $format = 'Y/m/d'): string
    {
        return Jalali::format($date, $format);
    }

    public function formatFa(mixed $date, string $format = 'Y/m/d H:i'): string
    {
        return Jalali::formatFa($date, $format);
    }

    public function toCarbon(string $jalaliDate): ?\DateTimeInterface
    {
        return Jalali::toCarbon($jalaliDate);
    }
}

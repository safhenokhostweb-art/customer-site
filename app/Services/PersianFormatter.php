<?php

namespace App\Services;

use App\Helpers\Jalali;
use App\Helpers\NumberHelper;

/**
 * PERSIAN LOCALIZATION GLOBAL STANDARD — Centralized Formatter (customer-site)
 *
 * Single entry point for ALL user-visible localization.
 * Never call number_format() or Carbon::format() directly in views.
 *
 * Blade directives (registered in AppServiceProvider):
 *   @numFa($n)  @priceFa($n)  @dateFa($d)  @dateFaLong($d)
 *   @dateFaShort($d)  @datetimeFa($d)  @agoFa($d)  @digitsFa($s)
 *
 * Global helpers: num_fa()  price_fa()  date_fa()  date_fa_long()
 *                 date_fa_short()  datetime_fa()  ago_fa()  digits_fa()
 */
final class PersianFormatter
{
    public static function num(int|float|null $value, int $decimals = 0): string
    {
        if ($value === null || $value === '') {
            return '۰';
        }
        return NumberHelper::toPersianNumbers(number_format((float) $value, $decimals));
    }

    public static function currency(int|float|null $value, string $unit = 'تومان', int $decimals = 0): string
    {
        return self::num($value, $decimals) . ' ' . $unit;
    }

    public static function date(mixed $date, string $format = 'Y/m/d'): string
    {
        if ($date === null || $date === '') {
            return '';
        }
        return Jalali::formatFa($date, $format);
    }

    public static function dateLong(mixed $date): string
    {
        if ($date === null || $date === '') {
            return '';
        }
        $str = Jalali::format($date, 'l j F Y');
        return NumberHelper::toPersianNumbers($str);
    }

    public static function dateShort(mixed $date): string
    {
        if ($date === null || $date === '') {
            return '';
        }
        $str = Jalali::format($date, 'j F Y');
        return NumberHelper::toPersianNumbers($str);
    }

    public static function datetime(mixed $date, string $format = 'Y/m/d H:i'): string
    {
        if ($date === null || $date === '') {
            return '';
        }
        return Jalali::formatFa($date, $format);
    }

    public static function ago(mixed $date): string
    {
        if ($date === null || $date === '') {
            return '';
        }
        return Jalali::diffForHumansFa($date);
    }

    public static function digits(string|int|float|null $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        return NumberHelper::toPersianNumbers((string) $value);
    }

    public static function jalaliToGregorian(?string $jalaliDate): ?string
    {
        if ($jalaliDate === null || trim($jalaliDate) === '') {
            return null;
        }
        $carbon = Jalali::toCarbon(trim($jalaliDate));
        return $carbon?->toDateString();
    }
}

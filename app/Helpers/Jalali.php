<?php

namespace App\Helpers;

use Carbon\Carbon;
use Hekmatinasser\Verta\Verta;

class Jalali
{
    /**
     * تبدیل تاریخ میلادی (Carbon/DateTime/string) به رشتهٔ شمسی.
     */
    public static function format($date, string $format = 'Y/m/d'): string
    {
        if ($date === null || $date === '') {
            return '';
        }
        if (is_string($date)) {
            try {
                $date = Carbon::parse($date);
            } catch (\Throwable $e) {
                return '';
            }
        }
        if (! $date instanceof Carbon && ! $date instanceof \DateTimeInterface) {
            return '';
        }
        try {
            return Verta::instance($date)->format($format);
        } catch (\Throwable $e) {
            return '';
        }
    }

    /** تبدیل تاریخ به شمسی با اعداد فارسی (۰-۹) برای نمایش */
    public static function formatFa($date, string $format = 'Y/m/d H:i'): string
    {
        $en = self::format($date, $format);
        if ($en === '') {
            return '';
        }
        $persianDigits = ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹'];
        return strtr($en, $persianDigits);
    }

    /**
     * تبدیل رشتهٔ تاریخ شمسی به Carbon (میلادی) برای ذخیره در دیتابیس.
     */
    public static function toCarbon(?string $jalaliDate): ?Carbon
    {
        if ($jalaliDate === null || trim($jalaliDate) === '') {
            return null;
        }
        $jalaliDate = trim($jalaliDate);
        $persianDigits = ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9'];
        $jalaliDate = strtr($jalaliDate, $persianDigits);
        $jalaliDate = str_replace(['/', '-', ' '], '-', $jalaliDate);
        $parts = array_filter(explode('-', $jalaliDate));
        if (count($parts) < 3) {
            return null;
        }
        try {
            $y = (int) $parts[0];
            $m = (int) $parts[1];
            $d = (int) $parts[2];
            $v = Verta::createJalaliDate($y, $m, $d);
            return $v->toCarbon();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function isJalaliString(?string $value): bool
    {
        if ($value === null || trim($value) === '') {
            return false;
        }
        $value = preg_replace('/[۰-۹]/', '0', trim($value));
        return (bool) preg_match('/^1[34]\d{2}[\-\/]/', $value);
    }

    public static function diffForHumansFa(mixed $date): string
    {
        if ($date === null || $date === '') {
            return '';
        }
        if (is_string($date)) {
            try {
                $date = Carbon::parse($date);
            } catch (\Throwable $e) {
                return '';
            }
        }
        if (!$date instanceof Carbon && !$date instanceof \DateTimeInterface) {
            return '';
        }

        $now    = Carbon::now();
        $carbon = $date instanceof Carbon ? $date : Carbon::instance($date);
        $diffSeconds = (int) abs($now->diffInSeconds($carbon));
        $isAgo  = $carbon->lte($now);
        $suffix = $isAgo ? ' پیش' : ' دیگر';

        $persian = ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴',
                    '5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹'];
        $n = fn(int $v): string => strtr((string)$v, $persian);

        if ($diffSeconds < 60) return 'همین الان';
        $diffMinutes = (int)($diffSeconds / 60);
        if ($diffMinutes < 60) return $n($diffMinutes) . ' دقیقه' . $suffix;
        $diffHours = (int)($diffSeconds / 3600);
        if ($diffHours < 24) return $n($diffHours) . ' ساعت' . $suffix;
        $diffDays = (int)($diffSeconds / 86400);
        if ($diffDays === 1) return $isAgo ? 'دیروز' : 'فردا';
        if ($diffDays < 30) return $n($diffDays) . ' روز' . $suffix;
        $diffMonths = (int) round($diffDays / 30);
        if ($diffMonths < 12) return $n($diffMonths) . ' ماه' . $suffix;
        $diffYears = (int) round($diffDays / 365);
        return $n($diffYears) . ' سال' . $suffix;
    }
}

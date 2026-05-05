<?php

namespace App\Helpers;

use Illuminate\Support\Str;

/**
 * بهینه‌سازی و تولید slug برای URL.
 */
class SlugHelper
{
    /**
     * تولید slug از متن فارسی یا انگلیسی.
     */
    public static function make(string $text): string
    {
        $slug = Str::slug($text, '-', 'fa');
        if ($slug === '') {
            $slug = self::transliterateFa($text);
        }
        return self::sanitize($slug);
    }

    /**
     * پاک‌سازی slug (حذف کاراکترهای نامعتبر).
     */
    public static function sanitize(string $slug): string
    {
        $slug = preg_replace('/[^\p{L}\p{N}\-_]/u', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }

    /**
     * تبدیل متن فارسی به حروف لاتین برای slug.
     */
    public static function transliterateFa(string $text): string
    {
        $map = [
            'آ' => 'a', 'ا' => 'a', 'ب' => 'b', 'پ' => 'p', 'ت' => 't', 'ث' => 's',
            'ج' => 'j', 'چ' => 'ch', 'ح' => 'h', 'خ' => 'kh', 'د' => 'd', 'ذ' => 'z',
            'ر' => 'r', 'ز' => 'z', 'ژ' => 'zh', 'س' => 's', 'ش' => 'sh', 'ص' => 's',
            'ض' => 'z', 'ط' => 't', 'ظ' => 'z', 'ع' => 'a', 'غ' => 'gh', 'ف' => 'f',
            'ق' => 'gh', 'ک' => 'k', 'گ' => 'g', 'ل' => 'l', 'م' => 'm', 'ن' => 'n',
            'و' => 'v', 'ه' => 'h', 'ی' => 'y', 'ي' => 'y', 'ئ' => 'y', ' ' => '-',
        ];
        $out = '';
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($chars as $c) {
            $out .= $map[$c] ?? (ctype_alnum($c) ? $c : '-');
        }
        return self::sanitize($out);
    }

    /**
     * تولید slug یکتا با افزودن عدد در صورت تکراری بودن.
     */
    public static function unique(string $base, callable $exists): string
    {
        $slug = self::make($base);
        if ($slug === '') {
            $slug = 'page';
        }
        if (!$exists($slug)) {
            return $slug;
        }
        $i = 1;
        while ($exists($slug . '-' . $i)) {
            $i++;
        }
        return $slug . '-' . $i;
    }

    /**
     * اعتبارسنجی slug (فقط حروف، اعداد، خط تیره).
     */
    public static function isValid(string $slug): bool
    {
        return (bool) preg_match('/^[\p{L}\p{N}\-_]+$/u', $slug) && strlen($slug) <= 255;
    }
}

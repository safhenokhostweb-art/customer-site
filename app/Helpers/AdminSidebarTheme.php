<?php

namespace App\Helpers;

use App\Models\Setting;

/**
 * رنگ‌های نوار کناری پنل ادمین و حساب کاربری (customer-site) — ذخیره در settings.
 */
class AdminSidebarTheme
{
    public const KEYS = [
        'admin_sidebar_bg1' => '#5a7fa8',
        'admin_sidebar_bg2' => '#456f94',
        'admin_sidebar_text' => '#f1f6fb',
        'admin_sidebar_muted' => '#d8ebfb',
        'admin_sidebar_accent' => '#7dd3fc',
        'admin_sidebar_border' => '#8aa9c4',
    ];

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        $out = [];
        foreach (self::KEYS as $key => $default) {
            $out[$key] = (string) Setting::get($key, $default);
        }

        return $out;
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'admin_sidebar_bg1' => 'نوار کناری: بالای گرادیان',
            'admin_sidebar_bg2' => 'نوار کناری: پایین گرادیان',
            'admin_sidebar_text' => 'نوار کناری: متن لینک‌ها',
            'admin_sidebar_muted' => 'نوار کناری: برچسب گروه و آیکن‌ها',
            'admin_sidebar_accent' => 'نوار کناری: تأکید و حالت فعال',
            'admin_sidebar_border' => 'نوار کناری: حاشیه',
        ];
    }
}

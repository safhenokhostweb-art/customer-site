<?php

namespace App\Services;

class ThemeCategoryHelper
{
    private static array $labels = [
        'corporate'   => 'شرکتی',
        'personal'    => 'شخصی',
        'blog'        => 'وبلاگ',
        'news'        => 'خبری',
        'educational' => 'آموزشی',
        'medical'     => 'پزشکی',
        'legal'       => 'حقوقی',
        'real_estate' => 'مسکن',
        'restaurant'  => 'رستوران',
        'portfolio'   => 'پورتفولیو',
        'agency'      => 'آژانس',
        'marketplace' => 'مارکت‌پلیس',
        'store'       => 'فروشگاه',
        'saas'        => 'SaaS',
        'landing'     => 'لندینگ پیج',
        'business'    => 'کسب‌وکار',
        'services'    => 'خدمات',
    ];

    public static function label(string $category): string
    {
        return self::$labels[$category] ?? $category;
    }

    public static function all(): array
    {
        return self::$labels;
    }
}

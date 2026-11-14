<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Available modules (config-based activation per installation)
    |--------------------------------------------------------------------------
    | Each module has a key, optional label, and env variable for activation.
    | Set MODULE_* to true/false in .env to enable/disable per installation.
    |
    | The 'enabled' key captures the env value at config:cache time so that
    | Module::enabled() works correctly when config is cached in production.
    */

    'modules' => [
        'blog' => [
            'name' => 'وبلاگ',
            'env' => 'MODULE_BLOG_ENABLED',
            'default' => true,
            'enabled' => env('MODULE_BLOG_ENABLED', true),
        ],
        'shop' => [
            'name' => 'فروشگاه',
            'env' => 'MODULE_SHOP_ENABLED',
            'default' => false,
            'enabled' => env('MODULE_SHOP_ENABLED', false),
        ],
        'coupon' => [
            'name' => 'کوپن تخفیف',
            'env' => 'MODULE_COUPON_ENABLED',
            'default' => false,
            'enabled' => env('MODULE_COUPON_ENABLED', false),
        ],
        'reviews' => [
            'name' => 'نظرات و امتیازدهی',
            'env' => 'MODULE_REVIEWS_ENABLED',
            'default' => false,
            'enabled' => env('MODULE_REVIEWS_ENABLED', false),
        ],
        'referral' => [
            'name' => 'معرفی دوستان',
            'env' => 'MODULE_REFERRAL_ENABLED',
            'default' => false,
            'enabled' => env('MODULE_REFERRAL_ENABLED', false),
        ],
        'subscription' => [
            'name' => 'اشتراک',
            'env' => 'MODULE_SUBSCRIPTION_ENABLED',
            'default' => false,
            'enabled' => env('MODULE_SUBSCRIPTION_ENABLED', false),
        ],
        'whatsapp' => [
            'name' => 'پاسخگویی شبکه‌های اجتماعی',
            'env' => 'MODULE_WHATSAPP_ENABLED',
            'default' => false,
            'enabled' => env('MODULE_WHATSAPP_ENABLED', false),
        ],
        'sms' => [
            'name' => 'پیامک',
            'env' => 'MODULE_SMS_ENABLED',
            'default' => false,
            'enabled' => env('MODULE_SMS_ENABLED', false),
        ],
        'courses' => [
            'name' => 'دوره‌ها',
            'env' => 'MODULE_COURSES_ENABLED',
            'default' => false,
            'enabled' => env('MODULE_COURSES_ENABLED', false),
        ],
        'invoice' => [
            'name' => 'فاکتور آنلاین',
            'env' => 'MODULE_INVOICE_ENABLED',
            'default' => false,
            'enabled' => env('MODULE_INVOICE_ENABLED', false),
        ],
        'business_card' => [
            'name' => 'کارت ویزیت دیجیتال',
            'env' => 'MODULE_BUSINESS_CARD_ENABLED',
            'default' => false,
            'enabled' => env('MODULE_BUSINESS_CARD_ENABLED', false),
        ],
        'social_links' => [
            'name' => 'لینک‌های شبکه‌های اجتماعی',
            'env' => 'MODULE_SOCIAL_LINKS_ENABLED',
            'default' => false,
            'enabled' => env('MODULE_SOCIAL_LINKS_ENABLED', false),
        ],
        'advertisements' => [
            'name' => 'تبلیغات',
            'env' => 'MODULE_ADVERTISEMENTS_ENABLED',
            'default' => false,
            'enabled' => env('MODULE_ADVERTISEMENTS_ENABLED', false),
        ],
        'forms' => [
            'name' => 'فرم‌ساز',
            'env' => 'MODULE_FORMS_ENABLED',
            'default' => false,
            'enabled' => env('MODULE_FORMS_ENABLED', false),
        ],
        'advanced_search' => [
            'name' => 'جستجوی پیشرفته',
            'env' => 'MODULE_ADVANCED_SEARCH_ENABLED',
            'default' => false,
            'enabled' => env('MODULE_ADVANCED_SEARCH_ENABLED', false),
        ],
        'currency_reform' => [
            'name' => 'حذف ۴ صفر پول ملی',
            'env' => 'MODULE_CURRENCY_REFORM_ENABLED',
            'default' => false,
            'enabled' => env('MODULE_CURRENCY_REFORM_ENABLED', false),
        ],
        // Site-specific module: entitlement comes from the license; the env
        // flag only matters on the owner site (SiteHelper::moduleEnabled).
        'solar_calculator' => [
            'name' => 'ماشین‌حساب خورشیدی',
            'env' => 'MODULE_SOLAR_CALCULATOR_ENABLED',
            'default' => false,
            'enabled' => env('MODULE_SOLAR_CALCULATOR_ENABLED', false),
        ],
        'menu' => [
            'name' => 'منو و ناوبری',
            'env' => 'MODULE_MENU_ENABLED',
            'default' => false,
            'enabled' => env('MODULE_MENU_ENABLED', false),
        ],
    ],

];

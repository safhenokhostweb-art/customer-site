<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Available modules (config-based activation per installation)
    |--------------------------------------------------------------------------
    | Each module has a key, optional label, and env variable for activation.
    | Set MODULE_* to true/false in .env to enable/disable per installation.
    */

    'modules' => [
        'blog' => [
            'name' => 'وبلاگ',
            'env' => 'MODULE_BLOG_ENABLED',
            'default' => true,
        ],
        'shop' => [
            'name' => 'فروشگاه',
            'env' => 'MODULE_SHOP_ENABLED',
            'default' => false,
        ],
        'coupon' => [
            'name' => 'کوپن تخفیف',
            'env' => 'MODULE_COUPON_ENABLED',
            'default' => false,
        ],
        'reviews' => [
            'name' => 'نظرات و امتیازدهی',
            'env' => 'MODULE_REVIEWS_ENABLED',
            'default' => false,
        ],
        'referral' => [
            'name' => 'معرفی دوستان',
            'env' => 'MODULE_REFERRAL_ENABLED',
            'default' => false,
        ],
        'subscription' => [
            'name' => 'اشتراک',
            'env' => 'MODULE_SUBSCRIPTION_ENABLED',
            'default' => false,
        ],
        'whatsapp' => [
            'name' => 'پاسخگویی شبکه‌های اجتماعی',
            'env' => 'MODULE_WHATSAPP_ENABLED',
            'default' => false,
        ],
        'sms' => [
            'name' => 'پیامک',
            'env' => 'MODULE_SMS_ENABLED',
            'default' => false,
        ],
        'courses' => [
            'name' => 'دوره‌ها',
            'env' => 'MODULE_COURSES_ENABLED',
            'default' => false,
        ],
        'invoice' => [
            'name' => 'فاکتور آنلاین',
            'env' => 'MODULE_INVOICE_ENABLED',
            'default' => false,
        ],
        'business_card' => [
            'name' => 'کارت ویزیت دیجیتال',
            'env' => 'MODULE_BUSINESS_CARD_ENABLED',
            'default' => false,
        ],
        'social_links' => [
            'name' => 'لینک‌های شبکه‌های اجتماعی',
            'env' => 'MODULE_SOCIAL_LINKS_ENABLED',
            'default' => false,
        ],
        'advertisements' => [
            'name' => 'تبلیغات',
            'env' => 'MODULE_ADVERTISEMENTS_ENABLED',
            'default' => false,
        ],
        'forms' => [
            'name' => 'فرم‌ساز',
            'env' => 'MODULE_FORMS_ENABLED',
            'default' => false,
        ],
        'advanced_search' => [
            'name' => 'جستجوی پیشرفته',
            'env' => 'MODULE_ADVANCED_SEARCH_ENABLED',
            'default' => false,
        ],
        'currency_reform' => [
            'name' => 'حذف ۴ صفر پول ملی',
            'env' => 'MODULE_CURRENCY_REFORM_ENABLED',
            'default' => false,
        ],
        'menu' => [
            'name' => 'منو و ناوبری',
            'env' => 'MODULE_MENU_ENABLED',
            'default' => false,
        ],
    ],

];

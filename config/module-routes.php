<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Module → Route mapping
    |--------------------------------------------------------------------------
    | هر ماژول به مسیرهای مربوطه نگاشت می‌شود.
    | برای جلوگیری از دسترسی به ماژول غیرفعال، middleware module:slug استفاده شود.
    */

    'blog' => [
        'admin.blog.*',
        'admin.categories.*',
        'admin.posts.*',
        'blog.*',
        'blog.category',
    ],

    'shop' => [
        'admin.products.*',
        'admin.product-categories.*',
        'admin.orders.*',
        'admin.coupons.*',
        'shop.*',
    ],

    'advanced_search' => [
        'search.*',
    ],

    'currency_reform' => [
        'admin.currency-reform.*',
    ],

    'solar_calculator' => [
        'solar-calculator.*',
        'admin.solar-calculator.*',
        'admin.exchange-rates.*',
    ],

    'advertisements' => [
        'admin.advertisements.*',
    ],

    'forms' => [
        'admin.forms.*',
    ],

    'coupon' => [
        'admin.coupons.*',
    ],

    'reviews' => [
        'admin.reviews.*',
    ],

    'referral' => [
        'admin.referrals.*',
    ],

    'subscription' => [
        'admin.subscriptions.*',
    ],

    'whatsapp' => [
        'admin.whatsapp.*',
        'admin.messaging.*',
    ],

    'sms' => [
        'admin.sms.*',
    ],

    'courses' => [
        'admin.courses.*',
    ],

    'invoice' => [
        'admin.invoices.*',
        'invoice.*',
    ],

    'business_card' => [
        'admin.business-cards.*',
        'business-card.*',
    ],

    'social_links' => [
        'admin.social-links.*',
    ],

    'notifications' => [
        // اعلان‌ها در داشبورد و ویجت — بدون مسیر اختصاصی
    ],

    'accounting' => [
        'admin.accounting.*',
    ],

    'seo_advanced' => [
        'admin.seo-advanced.*',
    ],

    'analytics' => [
        'admin.analytics.*',
    ],
    'announcements' => [
        'admin.announcements.*',
    ],
    'backup' => [
        'admin.backup.*',
    ],
    'brand' => [
        'admin.brand.*',
    ],
    'cloud' => [
        'admin.cloud.*',
    ],
    'contract' => [
        'admin.contract.*',
    ],
    'crm' => [
        'admin.crm.*',
    ],
    'discount_advanced' => [
        'admin.discount-advanced.*',
    ],
    'gallery' => [
        'admin.gallery.*',
    ],
    'i18n' => [
        'admin.i18n.*',
    ],
    'inventory' => [
        'admin.inventory.*',
    ],
    'marketing_automation' => [
        'admin.marketing-automation.*',
    ],
    'marketplace_sync' => [
        'admin.marketplace-sync.*',
    ],
    'multi_currency' => [
        'admin.multi-currency.*',
    ],
    'omnichannel' => [
        'admin.omnichannel.*',
    ],
    'podcast' => [
        'admin.podcast.*',
    ],
    'portfolio' => [
        'admin.portfolio.*',
    ],
    'product_bundling' => [
        'admin.product-bundling.*',
    ],
    'projects' => [
        'admin.projects.*',
    ],
    'qa' => [
        'admin.qa.*',
    ],
    'recruitment' => [
        'admin.recruitment.*',
    ],
    'services' => [
        'admin.services.*',
    ],
    'shipping' => [
        'admin.shipping.*',
    ],
    'tasks' => [
        'admin.tasks.*',
    ],
    'video' => [
        'admin.video.*',
    ],
    'wallet' => [
        'admin.wallet.*',
    ],

];

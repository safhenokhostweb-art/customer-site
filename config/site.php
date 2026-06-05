<?php

return [
    'name' => env('SITE_NAME', 'سایت شرکتی'),
    'theme' => env('SITE_THEME', 'default'),
    'locale' => env('SITE_LOCALE', 'fa'),

    /*
    | سایت مالک (فروش شما): وقتی true باشد، لایسنس برای کارکرد سایت الزامی نیست.
    | ماژول‌ها از .env (MODULE_BLOG_ENABLED و غیره) و ظاهر از پیش‌فرض پلتفرم استفاده می‌شود.
    | در صورت وارد کردن لایسنس، بروزرسانی و UX از پلتفرم دریافت می‌شود.
    */
    'owner_site' => filter_var(env('OWNER_SITE', false), FILTER_VALIDATE_BOOLEAN),

    /*
    | آدرس سایت پلتفرم اصلی (saas-platform) — بدون اسلش آخر.
    |
    | کاربرد:
    | - اعتبارسنجی لایسنس (صفحهٔ لایسنس در پنل)
    | - دریافت تنظیمات UX از پلتفرم: رنگ‌ها، فونت، لوگو، نام پلتفرم
    |
    | اگر خالی بگذارید: لایسنس از طریق API چک نمی‌شود و ظاهر سایت مشتری
    | با همان رنگ و فونت پیش‌فرض (مشابه پلتفرم) نمایش داده می‌شود.
    |
    | مثال (محلی):  http://saas-platform.test
    | مثال (سرور):  https://panel.example.com
    */
    'platform_url' => rtrim(env('PLATFORM_URL', ''), '/'),

    /*
    | شناسهٔ این سایت در جدول sites پلتفرم اصلی.
    | مورد استفادهٔ Education Center برای دریافت مقالات، راهنماها، و مسیرهای یادگیری.
    */
    'platform_site_id' => env('PLATFORM_SITE_ID') ? (int) env('PLATFORM_SITE_ID') : null,

    /*
    | توکن Sanctum ایجادشده در پلتفرم با نام customer-site-education-sync.
    | با این توکن از api/v1/education/sync/{siteId}/... احراز هویت می‌شود.
    | هرگز این مقدار را مستقیم در کد یا config ننویسید؛ فقط در .env تعریف کنید.
    */
    'platform_api_token' => env('PLATFORM_API_TOKEN', ''),

    /*
    | مدت کش تنظیمات و اعلان‌های پلتفرم (ثانیه).
    | پس از این مدت، درخواست بعدی به API پلتفرم می‌رود و دادهٔ تازه می‌گیرد.
    | پیش‌فرض ۳۰۰ = ۵ دقیقه (اعلان‌ها بدون بازخوانی دستی به‌روز می‌شوند).
    */
    'platform_config_cache_ttl' => (int) env('PLATFORM_CONFIG_CACHE_TTL', 300),
];

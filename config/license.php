<?php

return [
    /*
    | فاصلهٔ زمانی (دقیقه) بین هر بار اعتبارسنجی مجدد لایسنس با پلتفرم.
    | وقتی لایسنس از پلتفرم ابطال شود، حداکثر پس از این مدت سایت مشتری متوجه شده و ماژول‌ها را مخفی می‌کند.
    | مقدار ۰ = غیرفعال کردن re-validate خودکار (فقط هنگام ورود لایسنس اعتبارسنجی می‌شود).
    */
    'revalidate_interval_minutes' => (int) env('LICENSE_REVALIDATE_MINUTES', 5),

    /*
    | LICENSE_VERIFY_SSL=false زمانی که PLATFORM_URL از self-signed certificate استفاده می‌کند.
    | برای محیط‌هایی که customer-site و saas-platform روی یک سرور هستند و HTTPS داخلی
    | گواهی معتبر ندارد، این مقدار را false تنظیم کنید.
    | پیش‌فرض: true (SSL verification فعال)
    */
    'verify_ssl' => filter_var(env('LICENSE_VERIFY_SSL', true), FILTER_VALIDATE_BOOLEAN),
];

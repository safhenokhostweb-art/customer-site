<?php

return [
    /*
    | قالب‌های صفحهٔ کامل (Kits) برای سازندهٔ بصری — شرکتی، شخصی، فروشگاهی.
    | ساختار هر kit: id, label, description, accent (رنگ پیش‌نمایش UI), sections[].
    |
    | لیست kits در runtime از App\Support\BuilderPageKits::definitions() به ویو/کنترلر
    | پاس داده می‌شود؛ اینجا عمداً خالی است تا هنگام config:cache route() یا بوت ناقص اجرا نشود.
    */
    'kits' => [],
];

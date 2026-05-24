<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Forms Module Layout Configuration
    |--------------------------------------------------------------------------
    |
    | This config overrides the package default to match customer-site's
    | admin layout structure. The package default 'layouts.admin' does not
    | exist in this project.
    |
    */

    'layouts' => [
        /*
         * customer-site uses admin.layout (resources/views/admin/layout.blade.php)
         * PHASE 6.95 — TASK 6: Context-aware layout resolution.
         * The admin_tenant key is used when PlatformContextService detects
         * tenant context, ensuring the correct layout is always resolved.
         */
        'admin' => env('FORMS_LAYOUT_ADMIN', 'admin.layout'),

        /*
         * Tenant-specific admin layout (same as admin for customer-site,
         * but kept separate for clarity and future divergence).
         */
        'admin_tenant' => env('FORMS_LAYOUT_ADMIN_TENANT', 'admin.layout'),

        /*
         * Public/site-facing layout
         */
        'site' => env('FORMS_LAYOUT_SITE', 'themes.default.layout'),
    ],


    /*
    |--------------------------------------------------------------------------
    | Shared Components
    |--------------------------------------------------------------------------
    */

    'components' => [
        'module-guide-box' => env('FORMS_COMPONENT_MODULE_GUIDE_BOX', 'admin.partials.module-guide-box'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Types
    |--------------------------------------------------------------------------
    */

    'field_types' => [
        'text' => 'متن',
        'textarea' => 'متن بلند',
        'email' => 'ایمیل',
        'number' => 'عدد',
        'date' => 'تاریخ (شمسی)',
        'radio' => 'رادیو',
        'checkbox' => 'چک‌باکس',
        'select' => 'لیست انتخابی',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    'pagination' => env('FORMS_PAGINATION', 25),

    /*
    |--------------------------------------------------------------------------
    | Export Limit
    |--------------------------------------------------------------------------
    */

    'export_limit' => env('FORMS_EXPORT_LIMIT', 10000),

    /*
    |--------------------------------------------------------------------------
    | Feature Toggles
    |--------------------------------------------------------------------------
    */

    'seo_enabled' => env('FORMS_SEO_ENABLED', true),
    'captcha_enabled' => env('FORMS_CAPTCHA_ENABLED', true),
];

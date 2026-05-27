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
        'text'           => 'متن',
        'textarea'       => 'متن بلند',
        'email'          => 'ایمیل',
        'number'         => 'عدد',
        'phone'          => 'تلفن',
        'date'           => 'تاریخ (شمسی)',
        'radio'          => 'رادیو',
        'checkbox'       => 'چک‌باکس',
        'select'         => 'لیست انتخابی',
        'rating'         => 'امتیاز (ستاره)',
        'section_header' => 'عنوان بخش',
    ],

    'field_type_categories' => [
        [
            'label' => 'اطلاعات پایه',
            'types' => ['text', 'textarea', 'email', 'number', 'phone'],
        ],
        [
            'label' => 'انتخاب',
            'types' => ['select', 'radio', 'checkbox'],
        ],
        [
            'label' => 'تاریخ و امتیاز',
            'types' => ['date', 'rating'],
        ],
        [
            'label' => 'ساختار',
            'types' => ['section_header'],
        ],
    ],

    'field_type_icons' => [
        'text'           => 'bars-3-bottom-left',
        'textarea'       => 'document-text',
        'email'          => 'envelope',
        'number'         => 'hashtag',
        'phone'          => 'phone',
        'date'           => 'calendar',
        'radio'          => 'check-circle',
        'checkbox'       => 'check-badge',
        'select'         => 'chevron-up-down',
        'rating'         => 'star',
        'section_header' => 'minus',
    ],

    'pagination'    => env('FORMS_PAGINATION', 25),
    'export_limit'  => env('FORMS_EXPORT_LIMIT', 10000),
    'seo_enabled'   => env('FORMS_SEO_ENABLED', true),
    'captcha_enabled' => env('FORMS_CAPTCHA_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Site Model
    |--------------------------------------------------------------------------
    | customer-site is a single-tenant installation — no Site model exists.
    | Set to null so that Form::sites() and FormSubmission::site() return
    | empty / null relations gracefully instead of crashing on class_exists.
    */
    'site_model' => null,

    'date_formatter' => env('FORMS_DATE_FORMATTER', null),

    'automation' => [
        'enabled' => env('FORMS_AUTOMATION_ENABLED', true),
        'queue'   => env('FORMS_AUTOMATION_QUEUE', 'default'),
    ],

    'analytics' => [
        'enabled'      => env('FORMS_ANALYTICS_ENABLED', true),
        'default_days' => 30,
    ],
];

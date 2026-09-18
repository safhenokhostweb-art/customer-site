<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'hcaptcha' => [
        'skip_in_local' => filter_var(env('AUTH_CAPTCHA_SKIP_IN_LOCAL', false), FILTER_VALIDATE_BOOLEAN),
    ],

    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY', ''),
    ],

    'local_ai' => [
        'enabled' => filter_var(env('LOCAL_AI_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'base_url' => env('LOCAL_AI_BASE_URL', ''),
    ],

    'customer_provision_secret' => env('CUSTOMER_PROVISION_SECRET', ''),

    // TASK 3/3 Phase 6.5 (BLD-21) — MASTER_THEME_MEDIA_LOCALIZATION_GAP fix.
    // The ONLY origin(s) ThemeMediaLocalizationService is ever allowed to
    // fetch an image from at theme-install time — a fixed, operator-
    // controlled allowlist, never a customer- or theme-author-supplied
    // value. Defaults to the platform's own hostname (where every master
    // Theme's stock imagery is actually hosted, e.g.
    // https://panel.safhenokhost.ir/storage/theme-media/...); comma-
    // separated in THEME_MEDIA_TRUSTED_HOSTS for a CDN/asset-domain split.
    'trusted_media_hosts' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('THEME_MEDIA_TRUSTED_HOSTS', (string) parse_url(env('PLATFORM_URL', ''), PHP_URL_HOST)))
    ))),

];

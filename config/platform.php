<?php

return [
    // Both names remain supported, but env access is confined to config so
    // `php artisan config:cache` cannot disable platform communication.
    'api_url' => env('PLATFORM_API_URL', env('PLATFORM_URL', '')),
    'admin_token' => env('PLATFORM_ADMIN_TOKEN', env('PLATFORM_API_TOKEN', '')),
];

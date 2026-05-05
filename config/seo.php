<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sitemap
    |--------------------------------------------------------------------------
    */
    'sitemap_cache_ttl' => (int) env('SITEMAP_CACHE_TTL', 3600),
    'sitemap_max_urls_per_file' => (int) env('SITEMAP_MAX_URLS_PER_FILE', 50000),

    /*
    |--------------------------------------------------------------------------
    | Default meta
    |--------------------------------------------------------------------------
    */
    'default_robots' => env('SEO_DEFAULT_ROBOTS', 'index, follow'),
    'default_twitter_card' => env('SEO_TWITTER_CARD', 'summary'),
];

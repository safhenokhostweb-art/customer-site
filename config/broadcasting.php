<?php

return [

    'default' => env('BROADCAST_DRIVER', 'reverb'),

    'connections' => [

        /*
         * PHASE M3.2 — DOMAIN A Tenant Support Runtime
         *
         * Reverb is configured here for TENANT channels only.
         * Platform channels run on saas-platform's own Reverb server.
         * Channel names are scoped: private-tenant.{slug}.support.*
         * This guarantees Customer A can NEVER subscribe to Customer B channels.
         */
        'reverb' => [
            'driver'  => 'reverb',
            'key'     => env('REVERB_APP_KEY'),
            'secret'  => env('REVERB_APP_SECRET'),
            'app_id'  => env('REVERB_APP_ID'),
            'options' => [
                'host'   => env('REVERB_HOST', '127.0.0.1'),
                'port'   => env('REVERB_PORT', 8080),
                'scheme' => env('REVERB_SCHEME', 'http'),
                'useTLS' => env('REVERB_SCHEME', 'http') === 'https',
            ],
            'client_options' => [],
        ],

        'pusher' => [
            'driver' => 'pusher',
            'key'    => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster'   => env('PUSHER_APP_CLUSTER'),
                'encrypted' => true,
                'host'      => env('PUSHER_HOST'),
                'port'      => env('PUSHER_PORT', 443),
                'scheme'    => env('PUSHER_SCHEME', 'https'),
            ],
        ],

        'null' => [
            'driver' => 'null',
        ],

        'log' => [
            'driver' => 'log',
        ],
    ],

];

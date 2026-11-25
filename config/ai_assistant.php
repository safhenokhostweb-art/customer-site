<?php

return [
    /*
    | دستیار هوشمند محتوا — اتصال انحصاری به GapGPT
    */

    // GapGPT — OpenAI-compatible، در دسترس از VPS ایران
    'gapgpt' => [
        'api_key'  => env('GAPGPT_API_KEY', ''),
        'base_url' => env('GAPGPT_BASE_URL', 'https://api.gapgpt.app/v1'),
        'model'    => env('GAPGPT_MODEL', 'gpt-4o-mini'),
        'timeout'  => 60,
    ],

    'max_tokens'   => 4096,
    'temperature'  => 0.7,
    'queue_token'  => env('AI_QUEUE_TOKEN', ''),
];

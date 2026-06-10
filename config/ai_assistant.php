<?php

return [
    /*
    | دستیار هوشمند محتوا
    | اول DeepSeek، اگر در دسترس نبود Claude جایگزین می‌شود.
    */

    'primary'   => env('AI_ASSISTANT_PROVIDER', 'deepseek'),
    'fallback'  => env('AI_ASSISTANT_FALLBACK', 'claude'),

    'deepseek' => [
        'api_key'  => env('DEEPSEEK_API_KEY', ''),
        'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com/v1'),
        'model'    => env('DEEPSEEK_MODEL', 'deepseek-chat'),
        'timeout'  => 60,
    ],

    'claude' => [
        'api_key'  => env('CLAUDE_API_KEY', ''),
        'base_url' => 'https://api.anthropic.com/v1',
        'model'    => env('CLAUDE_MODEL', 'claude-sonnet-4-6'),
        'timeout'  => 60,
    ],

    'max_tokens'   => 4096,
    'temperature'  => 0.7,
    'queue_token'  => env('AI_QUEUE_TOKEN', ''),
];

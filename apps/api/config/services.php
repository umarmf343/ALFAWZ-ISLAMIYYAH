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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    /*
    |--------------------------------------------------------------------------
    | AlFawz Qur'an Institute Services
    |--------------------------------------------------------------------------
    */

    'paystack' => [
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'payment_url' => env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'organization' => env('OPENAI_ORGANIZATION'),
    ]

    'quran' => [
        'base' => env('QURAN_API_BASE', 'https://api.quran.com/api/v4'),
        'timeout' => env('QURAN_API_TIMEOUT', 30),
        'cache_ttl' => env('QURAN_API_CACHE_TTL', 1440), // 24 hours in minutes
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'organization' => env('OPENAI_ORGANIZATION'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'timeout' => env('OPENAI_TIMEOUT', 60),
        'whisper' => [
            'model' => env('TAJWEED_WHISPER_MODEL', 'whisper-1'),
            'language' => 'ar', // Arabic
            'response_format' => 'verbose_json',
            'temperature' => 0,
        ],
    ],

    'tajweed' => [
        'enabled' => env('TAJWEED_ENABLED', true),
        'queue' => env('TAJWEED_QUEUE', 'default'),
        'storage' => [
            'disk' => env('FILESYSTEM_DISK', 's3'),
            'audio_path' => 'tajweed/audio',
            'results_path' => 'tajweed/results',
        ],
        'processing' => [
            'max_concurrent' => env('TAJWEED_MAX_CONCURRENT_JOBS', 5),
            'timeout' => env('TAJWEED_ANALYSIS_TIMEOUT', 300),
            'retry_attempts' => 3,
        ],
    ],

];

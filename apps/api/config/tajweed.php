<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

return [
    /*
    |--------------------------------------------------------------------------
    | Tajweed Analysis Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for the Tajweed analysis system including
    | audio processing, OpenAI integration, and system limits.
    |
    */

    'audio' => [
        'max_size' => env('TAJWEED_AUDIO_MAX_SIZE', 10485760), // 10MB in bytes
        'allowed_formats' => explode(',', env('TAJWEED_AUDIO_FORMATS', 'mp3,wav,m4a,ogg')),
        'storage_disk' => env('FILESYSTEM_DISK', 's3'),
        'storage_path' => 'tajweed/audio',
    ],

    'processing' => [
        'max_concurrent_jobs' => env('TAJWEED_MAX_CONCURRENT_JOBS', 5),
        'timeout_seconds' => env('TAJWEED_ANALYSIS_TIMEOUT', 300),
        'retry_attempts' => 3,
        'retry_delay' => 60, // seconds
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'whisper_model' => env('TAJWEED_WHISPER_MODEL', 'whisper-1'),
        'max_retries' => 3,
        'timeout' => 120,
    ],

    'retention' => [
        'audio_files_days' => env('TAJWEED_RETENTION_DAYS', 30),
        'failed_jobs_days' => 7,
        'completed_jobs_days' => 90,
    ],

    'scoring' => [
        'weights' => [
            'pronunciation' => 0.4,
            'tajweed_rules' => 0.3,
            'fluency' => 0.2,
            'pace' => 0.1,
        ],
        'pass_threshold' => 70,
        'excellence_threshold' => 90,
    ],

    'rules' => [
        'categories' => [
            'ghunna' => 'Ghunna (Nasal Sound)',
            'qalqalah' => 'Qalqalah (Echo)',
            'madd' => 'Madd (Elongation)',
            'idgham' => 'Idgham (Merging)',
            'ikhfa' => 'Ikhfa (Hiding)',
            'iqlab' => 'Iqlab (Conversion)',
            'izhar' => 'Izhar (Clear Pronunciation)',
            'waqf' => 'Waqf (Stopping)',
        ],
        'severity_levels' => [
            'minor' => 'Minor Error',
            'moderate' => 'Moderate Error', 
            'major' => 'Major Error',
            'critical' => 'Critical Error',
        ],
    ],

    'ui' => [
        'polling_interval_ms' => 2000,
        'auto_play_feedback' => true,
        'highlight_colors' => [
            'minor' => '#fef3c7',    // yellow-100
            'moderate' => '#fed7aa',  // orange-200
            'major' => '#fecaca',     // red-200
            'critical' => '#fca5a5',  // red-300
        ],
    ],
];
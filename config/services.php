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

    'master_api' => [
        'url' => env('MASTER_API_URL', 'http://127.0.0.1:8000'),

        // Batas waktu (detik) saat menarik data master ketika sync manual.
        'timeout' => env('MASTER_API_TIMEOUT', 30),

        // Bila data master lokal belum pernah di-sync, ambil langsung dari API
        // sekali jalan supaya aplikasi tidak kosong. Matikan (false) kalau lokasi
        // pemakaian benar-benar tanpa internet.
        'fallback' => env('MASTER_API_FALLBACK', true),
    ],

];

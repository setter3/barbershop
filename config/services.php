<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'zibal' => [
        'merchant' => env('ZIBAL_MERCHANT'),
        'request_url' => env('ZIBAL_REQUEST_URL', 'https://gateway.zibal.ir/v1/request'),
        'verify_url' => env('ZIBAL_VERIFY_URL', 'https://gateway.zibal.ir/v1/verify'),
        'inquiry_url' => env('ZIBAL_INQUIRY_URL', 'https://gateway.zibal.ir/v1/inquiry'),
        'start_url' => env('ZIBAL_START_URL', 'https://gateway.zibal.ir/start'),
        'payment_hold_minutes' => (int) env('ZIBAL_PAYMENT_HOLD_MINUTES', 20),
    ],

];

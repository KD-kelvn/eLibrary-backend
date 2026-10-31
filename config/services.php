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

    /*
    | SMS providers (V1-006). Runtime overrides live in provider_settings (encrypted).
    | Driver: log | beem | institutional
    */
    'university' => [
        'hmac_secret' => env('UNIVERSITY_SYNC_HMAC_SECRET', ''),
        'timestamp_tolerance_seconds' => (int) env('UNIVERSITY_SYNC_TIMESTAMP_TOLERANCE', 300),
    ],

    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
        'beem' => [
            // Same env names as family-hub; secrets stay in .env / settings.providers (never committed).
            'url' => env('BEEM_SMS_URL', 'https://apisms.beem.africa/v1/send'),
            'api_key' => env('BEEM_API_KEY', ''),
            'secret_key' => env('BEEM_SECRET_KEY', ''),
            'sender_id' => env('BEEM_SENDER_ID', ''),
            'balance_url' => env('BEEM_BALANCE_URL', 'https://apisms.beem.africa/public/v1/vendors/balance'),
        ],
        'institutional' => [
            'base_url' => env('SMS_INSTITUTIONAL_BASE_URL', ''),
            'send_path' => env('SMS_INSTITUTIONAL_SEND_PATH', '/sms/send'),
            'health_url' => env('SMS_INSTITUTIONAL_HEALTH_URL', ''),
            'api_key' => env('SMS_INSTITUTIONAL_API_KEY', ''),
            'api_secret' => env('SMS_INSTITUTIONAL_API_SECRET', ''),
            'api_key_header' => env('SMS_INSTITUTIONAL_API_KEY_HEADER', 'X-Api-Key'),
            'auth_scheme' => env('SMS_INSTITUTIONAL_AUTH_SCHEME', 'header'),
            'sender_id' => env('SMS_INSTITUTIONAL_SENDER_ID', ''),
            'to_field' => env('SMS_INSTITUTIONAL_TO_FIELD', 'to'),
            'message_field' => env('SMS_INSTITUTIONAL_MESSAGE_FIELD', 'message'),
            'sender_field' => env('SMS_INSTITUTIONAL_SENDER_FIELD', 'sender_id'),
        ],
    ],

];

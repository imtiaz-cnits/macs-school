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

    'mim_sms' => [
        'username' => env('MIM_SMS_USERNAME'),
        'api_key' => env('MIM_SMS_API_KEY'),
        'sender_id' => env('MIM_SMS_SENDER_ID'),
        'test_mode' => env('SMS_TEST_MODE', true),
        'test_recipient' => env('SMS_TEST_RECIPIENT'),
    ],

    'zkteco' => [
        'ip' => env('ZKTECO_IP', '192.168.1.201'),
        'port' => (int) env('ZKTECO_PORT', 4370),
        'timeout' => (int) env('ZKTECO_TIMEOUT', 3),
        'mode' => env('ZKTECO_MODE'),
    ],

];

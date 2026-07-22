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
    | MEGA (external private file archive â€” server-side only)
    |--------------------------------------------------------------------------
    |
    | Credentials are passed to Node via process environment, never CLI args.
    | Browser Document Library session endpoints are separate and unchanged.
    |
    */
    'mega' => [
        'email' => env('MEGA_EMAIL'),
        'password' => env('MEGA_PASSWORD'),
        'base_folder' => env('MEGA_BASE_FOLDER', 'digital.kotor'),
        'node_binary' => env('MEGA_NODE_BINARY'),
        'user_agent' => env('MEGA_USER_AGENT', 'DigitalKotorArchive/1.0'),
    ],

];

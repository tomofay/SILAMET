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

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'telegram' => [
        'enabled' => filter_var(env('TELEGRAM_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'group_chat_id' => env('TELEGRAM_GROUP_CHAT_ID'),
        'admin_chat_id' => env('TELEGRAM_ADMIN_CHAT_ID'),
        'ca_bundle' => env('TELEGRAM_CA_BUNDLE'),
        'timeout' => (int) env('TELEGRAM_TIMEOUT', 5),
        'gate_cooldown_seconds' => (int) env('TELEGRAM_GATE_COOLDOWN_SECONDS', 15),
        'overdue_reminder_hours' => (int) env('TELEGRAM_OVERDUE_REMINDER_HOURS', 4),
        'daily_summary_enabled' => filter_var(env('TELEGRAM_DAILY_SUMMARY_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'notify_ticket_resolved' => filter_var(env('TELEGRAM_NOTIFY_TICKET_RESOLVED', false), FILTER_VALIDATE_BOOLEAN),
    ],

];

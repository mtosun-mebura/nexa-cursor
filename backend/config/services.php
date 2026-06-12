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

    'vonage' => [
        'api_key' => env('VONAGE_API_KEY'),
        'api_secret' => env('VONAGE_API_SECRET'),
        'from' => env('VONAGE_FROM_NUMBER'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    ],

    'ai_chat' => [
        'webhook_url' => env('AI_CHAT_WEBHOOK_URL'),
        'module_defaults' => [
            'taxi' => env(
                'NEXA_TAXI_ASSISTANT_WEBHOOK_URL',
                'https://automations.nexasuite.nl/webhook/nexa-taxi-assistant'
            ),
        ],
    ],

    'nexa_taxi' => [
        'assistant_webhook_url' => env(
            'NEXA_TAXI_ASSISTANT_WEBHOOK_URL',
            'https://automations.nexasuite.nl/webhook/nexa-taxi-assistant'
        ),
    ],

];

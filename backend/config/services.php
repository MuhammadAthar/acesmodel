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

    // AI Providers
    'ai' => [
        'provider' => env('AI_DEFAULT_PROVIDER', 'replicate'),
    ],

    'replicate' => [
        'token' => env('REPLICATE_API_TOKEN'),
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
    ],

    // Payment Gateways
    'stripe' => [
        'key'             => env('STRIPE_KEY'),
        'secret'          => env('STRIPE_SECRET'),
        'webhook_secret'  => env('STRIPE_WEBHOOK_SECRET'),
        'price_starter'   => env('STRIPE_PRICE_STARTER'),
        'price_growth'    => env('STRIPE_PRICE_GROWTH'),
        'price_agency'    => env('STRIPE_PRICE_AGENCY'),
    ],

    'easypaisa' => [
        'store_id' => env('EASYPAISA_STORE_ID'),
        'hash_key' => env('EASYPAISA_HASH_KEY'),
    ],

    'jazzcash' => [
        'merchant_id'    => env('JAZZCASH_MERCHANT_ID'),
        'password'       => env('JAZZCASH_PASSWORD'),
        'integrity_salt' => env('JAZZCASH_INTEGRITY_SALT'),
    ],

    'bank_transfer' => [
        'bank_name'      => env('BANK_NAME', 'Meezan Bank'),
        'account_title'  => env('BANK_ACCOUNT_TITLE'),
        'account_number' => env('BANK_ACCOUNT_NUMBER'),
        'iban'           => env('BANK_IBAN'),
    ],

    'frontend_url' => env('FRONTEND_URL', 'http://localhost:5173'),

];

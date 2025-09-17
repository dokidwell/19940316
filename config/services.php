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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | 鲸探 (Whale Explorer) API Configuration
    |--------------------------------------------------------------------------
    */
    'whale' => [
        'app_id' => env('WHALE_APP_ID'),
        'private_key' => env('WHALE_PRIVATE_KEY'),
        'public_key' => env('WHALE_PUBLIC_KEY'),
        'base_url' => env('WHALE_BASE_URL', 'https://openapi.alipay.com'),
        'gateway_url' => env('WHALE_GATEWAY_URL', 'https://openapi.alipay.com/gateway.do'),
        'redirect_uri' => env('WHALE_REDIRECT_URI', 'https://hoho.community/auth/whale/callback'),
        'market_api_url' => env('WHALE_MARKET_API_URL'),
        'market_api_key' => env('WHALE_MARKET_API_KEY'),
        'market_cache_timeout' => env('WHALE_MARKET_CACHE_TIMEOUT', 1800),
    ],

    /*
    |--------------------------------------------------------------------------
    | 腾讯云 (Tencent Cloud) Configuration
    |--------------------------------------------------------------------------
    */
    'tencent' => [
        'cos' => [
            'secret_id' => env('TENCENT_COS_SECRET_ID'),
            'secret_key' => env('TENCENT_COS_SECRET_KEY'),
            'region' => env('TENCENT_COS_REGION', 'ap-shanghai'),
            'bucket' => env('TENCENT_COS_BUCKET'),
            'domain' => env('TENCENT_COS_DOMAIN'),
        ],
        'sms' => [
            'secret_id' => env('TENCENT_SMS_SECRET_ID'),
            'secret_key' => env('TENCENT_SMS_SECRET_KEY'),
            'sdk_app_id' => env('TENCENT_SMS_SDK_APP_ID'),
            'sign_name' => env('TENCENT_SMS_SIGN_NAME', 'HOHO社区'),
            'template_id' => env('TENCENT_SMS_TEMPLATE_ID'),
        ],
    ],

];

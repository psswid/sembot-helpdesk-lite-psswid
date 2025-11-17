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

    /*
    |--------------------------------------------------------------------------
    | LLM / Triage Assistant Configuration
    |--------------------------------------------------------------------------
    |
    | Central configuration for the triage assistant. The driver determines
    | which implementation the application will use when generating triage
    | suggestions. In local/dev you can set `LLM_DRIVER=mock` to avoid any
    | external API costs while still receiving deterministic suggestions.
    |
    | Supported drivers:
    |   - mock   : heuristic, offline suggestions
    |   - openai : real LLM calls via OpenRouter compatible endpoint
    |
    | Timeout is kept intentionally low to avoid blocking requests for too
    | long – failures gracefully fall back to a lightweight heuristic when
    | using the real driver.
    */
    'llm' => [
        'driver' => env('LLM_DRIVER', 'mock'),
        'base_url' => env('LLM_BASE_URL', 'https://openrouter.ai/api'),
        'model' => env('LLM_MODEL', 'openai/gpt-oss-20b:free'),
        'api_key' => env('LLM_API_KEY', env('OPENROUTER_API_KEY')),
        'timeout' => (int) env('LLM_TIMEOUT', 5),
    ],

];

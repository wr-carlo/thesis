<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Provider Configurations
    |--------------------------------------------------------------------------
    |
    | Configure the AI providers for assessment generation.
    | Each provider has a single model with specific token limits.
    |
    */

    'providers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => 'gpt-3.5-turbo',
            'limits' => [
                'max_input_tokens' => 16385,
                'max_output_tokens' => 4096,
                'safe_limit' => 12000,
            ],
        ],
        'groq' => [
            'api_key' => env('GROQ_API_KEY'),
            'model' => 'llama-3.3-70b-versatile',
            'limits' => [
                'max_input_tokens' => 128000,
                'max_output_tokens' => 32768,
                'safe_limit' => 100000,
            ],
        ],
        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'model' => 'gemini-2.5-flash',
            'limits' => [
                'max_input_tokens' => 1048576,
                'max_output_tokens' => 65536,
                'safe_limit' => 800000,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Chunking Settings
    |--------------------------------------------------------------------------
    |
    | Configure how content is chunked when it exceeds model token limits.
    |
    */

    'chunking' => [
        'buffer_tokens' => 10000,
        'overlap_percentage' => 0.05,
        'tokens_per_word' => 1.3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Primary Provider
    |--------------------------------------------------------------------------
    |
    | The primary provider to use for AI generation.
    | This will be tried first before falling back to other providers.
    |
    */

    'primary_provider' => 'openai',

    /*
    |--------------------------------------------------------------------------
    | Fallback Order
    |--------------------------------------------------------------------------
    |
    | The order in which providers will be tried if the primary fails.
    |
    */

    'fallback_order' => [
        'openai',
        'gemini',
        'groq',
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum time (in seconds) to wait for an AI provider response.
    |
    */

    'timeout' => 120,

];

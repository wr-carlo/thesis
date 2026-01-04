<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Provider Configurations
    |--------------------------------------------------------------------------
    |
    | Configure the AI providers and their models for assessment generation.
    | Each provider has specific token limits and capabilities.
    |
    */

    'providers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'models' => [
                'gpt-4o-mini' => [
                    'max_input_tokens' => 128000,
                    'max_output_tokens' => 16384,
                    'safe_limit' => 100000, // 80% of max for safety
                ],
                'gpt-4' => [
                    'max_input_tokens' => 8192,
                    'max_output_tokens' => 4096,
                    'safe_limit' => 6000,
                ],
            ],
        ],
        'anthropic' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'models' => [
                'claude-3-5-sonnet-20241022' => [
                    'max_input_tokens' => 200000,
                    'max_output_tokens' => 8192,
                    'safe_limit' => 160000,
                ],
                'claude-3-haiku-20240307' => [
                    'max_input_tokens' => 200000,
                    'max_output_tokens' => 4096,
                    'safe_limit' => 160000,
                ],
            ],
        ],
        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'models' => [
                'gemini-2.5-flash' => [
                    'max_input_tokens' => 32768,
                    'max_output_tokens' => 8192,
                    'safe_limit' => 25000,
                ],
                // 'gemini-1.5-pro' => [
                //     'max_input_tokens' => 1000000,
                //     'max_output_tokens' => 8192,
                //     'safe_limit' => 800000,
                // ],
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
        'buffer_tokens' => 10000, // Reserve for prompt/response overhead
        'overlap_percentage' => 0.05, // 5% overlap between chunks for context
        'tokens_per_word' => 1.3, // Average tokens per word conversion factor
    ],

    /*
    |--------------------------------------------------------------------------
    | Primary Provider & Model
    |--------------------------------------------------------------------------
    |
    | The primary provider and model to use for AI generation.
    | This will be tried first before falling back to other providers.
    |
    */

    'primary_provider' => 'openai',
    'primary_model' => 'gpt-4o-mini',

    /*
    |--------------------------------------------------------------------------
    | Fallback Order
    |--------------------------------------------------------------------------
    |
    | The order in which providers will be tried if the primary fails.
    | Each provider will be tried once before moving to the next.
    |
    */

    'fallback_order' => [
        'openai',
        'anthropic',
        'gemini',
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum time (in seconds) to wait for an AI provider response.
    | After this timeout, the request will fail and try the next provider.
    |
    */

    'timeout' => 30,

];


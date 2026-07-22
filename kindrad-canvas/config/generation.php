<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Generation Pipeline
    |--------------------------------------------------------------------------
    |
    | Configuration for the GenerationProvider contract and the provider
    | registry. The `provider` slug selects which GenerationProvider
    | implementation will be used at runtime.
    |
    */

    'provider' => env('GENERATION_PROVIDER', 'openai'),

    'disk' => env('GENERATION_DISK', 's3'),

    'key_prefix' => env('GENERATION_KEY_PREFIX', 'generations/'),

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_IMAGE_MODEL', 'dall-e-3'),
        'size' => env('OPENAI_IMAGE_SIZE', '1024x1024'),
        'endpoint' => env('OPENAI_IMAGE_ENDPOINT', 'https://api.openai.com/v1/images/generations'),
        'timeout' => (int) env('OPENAI_IMAGE_TIMEOUT', 120),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_IMAGE_MODEL', 'gemini-2.5-flash-image'),
        'endpoint' => env('GEMINI_IMAGE_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent'),
        'timeout' => (int) env('GEMINI_IMAGE_TIMEOUT', 120),
    ],

];

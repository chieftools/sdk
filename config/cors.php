<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => [
        // These routes are protected with token (not session) and are therefore CORS safe
        'horizon/*',
        'api/graphql',
        'api/graphql/schema',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        env('APP_DEBUG')
            ? '*'
            : config('app.domain'),
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['phpdebugbar-id'],

    'max_age' => 3600, // 1 hour, this prevents a preflight request for every request

    'supports_credentials' => false,

];

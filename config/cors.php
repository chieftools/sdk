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

    // These routes are protected with token (not session) auth or public in general and are therefore CORS safe
    'paths' => [
        'horizon/*',
        'api/graphql',
        '.well-known/*',
        'api/graphql/schema',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['phpdebugbar-id'],

    'max_age' => 3600, // 1 hour, this prevents a preflight request for every request

    'supports_credentials' => false,

];

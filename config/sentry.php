<?php

return [
    'dsn' => env('APP_DEBUG', false) ? null : env('SENTRY_PRIVATE_DSN', env('SENTRY_LARAVEL_DSN')),

    'release' => config('app.version'),

    'send_default_pii' => false,
];

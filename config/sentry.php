<?php

return [

    'dsn' => env('APP_DEBUG', false) ? null : env('SENTRY_PRIVATE_DSN', env('SENTRY_LARAVEL_DSN')),

    'release' => env('APP_COMMIT_SHA', config('app.version')),

    'error_types' => E_ALL ^ E_DEPRECATED ^ E_USER_DEPRECATED,

    'traces_sampler' => [ChiefTools\SDK\Exceptions\Sentry::class, 'tracesSampler'],

    'in_app_exclude' => [
        base_path('vendor'),
        app_path('Http/Middleware'),
    ],

    'in_app_include' => [
        base_path('vendor/chieftools'),
    ],

    'send_default_pii' => false,

    'class_serializers' => [
        Illuminate\Queue\Jobs\Job::class          => [ChiefTools\SDK\Exceptions\Sentry::class, 'serializeJob'],
        Illuminate\Database\Eloquent\Model::class => [ChiefTools\SDK\Exceptions\Sentry::class, 'serializeEloquentModel'],
    ],

    'trace_propagation_targets' => [

        'tny.app',
        'bill.do',
        'chief.app',
        'chief.tools',
        'pkgtrends.app',
        'ip.chief.tools',
        'cert.chief.app',
        'deploy.chief.app',
        'account.chief.app',

    ],

];

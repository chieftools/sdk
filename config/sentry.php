<?php

$appNamespace = ucfirst(config('chief.namespace') ?? config('chief.id'));

return [

    'dsn' => env('APP_DEBUG', false) ? null : env('SENTRY_PRIVATE_DSN', env('SENTRY_LARAVEL_DSN')),

    'release' => config('app.version'),

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

    // @see: https://docs.sentry.io/platforms/php/guides/laravel/configuration/options/#profiles-sample-rate
    'profiles_sample_rate' => env('SENTRY_PROFILES_SAMPLE_RATE') === null ? null : (float)env('SENTRY_PROFILES_SAMPLE_RATE'),

];

<?php

$appNamespace = ucfirst(config('chief.namespace') ?? config('chief.id'));

return [

    'dsn' => env('APP_DEBUG', false) ? null : env('SENTRY_PRIVATE_DSN', env('SENTRY_LARAVEL_DSN')),

    'release' => config('app.version'),

    'error_types' => E_ALL ^ E_DEPRECATED ^ E_USER_DEPRECATED,

    'traces_sampler' => [IronGate\Chief\Exceptions\Sentry::class, 'tracesSampler'],

    'in_app_exclude' => [
        base_path('vendor'),
        app_path('Http/Middleware'),
    ],

    'in_app_include' => [
        base_path('vendor/irongate'),
    ],

    'send_default_pii' => false,

    'class_serializers' => [
        Illuminate\Queue\Jobs\Job::class          => [IronGate\Chief\Exceptions\Sentry::class, 'serializeJob'],
        Illuminate\Database\Eloquent\Model::class => [IronGate\Chief\Exceptions\Sentry::class, 'serializeEloquentModel'],
    ],

    'controllers_base_namespace' => "IronGate\\{$appNamespace}\\Http\\Controllers",

];

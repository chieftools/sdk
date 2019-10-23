<?php

return [

    'id' => null,

    'auth' => [

        'model' => IronGate\Integration\Entities\User::class,

        'redirect' => '/dashboard',

    ],

    'routes' => [

        'middleware' => ['web'],

    ],

    'base_url' => env('CHIEF_BASE_URL', 'https://account.chief.app'),

    'webhooks' => [

        'account_closed'  => IronGate\Integration\Webhook\Handlers\AccountClosed::class,
        'account_updated' => IronGate\Integration\Webhook\Handlers\AccountUpdated::class,

    ],

];

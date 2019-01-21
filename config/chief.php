<?php

return [

    'auth' => [

        'model' => IronGate\Integration\Entities\User::class,

        'redirect' => '/dashboard',

    ],

    'routes' => [

        'middleware' => ['web'],

    ],

    'webhooks' => [

        'account_closed'  => IronGate\Integration\Webhook\Handlers\AccountClosed::class,
        'account_updated' => IronGate\Integration\Webhook\Handlers\AccountUpdated::class,

    ],

];

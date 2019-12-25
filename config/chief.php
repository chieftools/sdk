<?php

return [

    'id' => null,

    'auth' => [

        'model' => IronGate\Integration\Entities\User::class,

        'redirect' => '/dashboard',

    ],

    'routes' => [

        'api' => [

            'middleware' => ['api'],

        ],

        'web' => [

            'middleware' => ['web'],

            'api' => [

                'middleware' => ['web', 'auth'],

            ],

        ],

    ],

    'base_url' => env('CHIEF_BASE_URL', 'https://account.chief.app'),

    'webhooks' => [

        'account_closed'  => IronGate\Integration\Webhook\Handlers\AccountClosed::class,
        'account_updated' => IronGate\Integration\Webhook\Handlers\AccountUpdated::class,

    ],

    'chronicle' => [

        'url' => 'https://chronicle.devdomein.nl/chronicle/replica/LogbDtnCLxxdWsrtUQKAueytA7igS2p9Y_ZUz8L-QbZ2/',

        'pubkey' => 'Bgcc1QfkP0UNgMZuHzi0hC1hA1SoVAyUrskmSkzRw3E=',

    ],

];

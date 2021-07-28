<?php

return [

    'id' => null,

    'auth' => [

        'model' => IronGate\Chief\Entities\User::class,

        'redirect' => '/dashboard',

    ],

    'queue' => [

        'monitor' => env('QUEUE_MONITOR_URL'),

    ],

    'routes' => [

        'api' => [

            'prefix'     => 'api',
            'middleware' => ['api'],

        ],

        'web' => [

            'middleware' => ['web'],

        ],

        'web-api' => [

            'prefix'     => 'api',
            'middleware' => ['web', 'auth'],

        ],

        'passport' => [],

    ],

    'graphql' => [

        'namespace' => [

            'prefix' => 'Http',

        ],

        'playground' => env('CHIEF_GRAPHQL_PLAYGROUND', 'graphiql'),

        'subscriptions' => [

            'enabled' => env('CHIEF_GRAPHQL_SUBSCRIPTIONS_ENABLED', false),

            'webhook_secret' => env('CHIEF_GRAPHQL_SUBSCRIPTIONS_WEBHOOK_SECRET'),

        ],

    ],

    'app_home' => env('CHIEF_APP_HOME'),

    'base_url' => env('CHIEF_BASE_URL', 'https://account.chief.app'),

    'site_url' => env('CHIEF_SITE_URL', 'https://chief.app'),

    'response' => [

        'securityheaders' => [

            'Feature-Policy'            => "accelerometer 'none'; camera 'none'; geolocation 'none'; gyroscope 'none'; magnetometer 'none'; microphone 'none'; payment 'none'; usb 'none'",
            'Referrer-Policy'           => 'strict-origin-when-cross-origin',
            'X-Frame-Options'           => 'SAMEORIGIN',
            'X-Content-Type-Options'    => 'nosniff',
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',

        ],

    ],

    'webhooks' => [

        'account_closed'  => IronGate\Chief\Webhook\Handlers\AccountClosed::class,
        'account_updated' => IronGate\Chief\Webhook\Handlers\AccountUpdated::class,

    ],

    'chronicle' => [

        'url' => 'https://chronicle.devdomein.nl/chronicle/replica/LogbDtnCLxxdWsrtUQKAueytA7igS2p9Y_ZUz8L-QbZ2/',

        'pubkey' => 'Bgcc1QfkP0UNgMZuHzi0hC1hA1SoVAyUrskmSkzRw3E=',

    ],

    'preferences' => [

//        'preference_key' => [
//            'This is an example preference', // (string) Title of the preference
//            'When enabled this example preference should do exactly nothing.', // (string) Longer description
//            'hashtag', // (string) Font Awesome icon name
//            false, // (bool) default value
//            'category', // (string) reference to a preference category
//        ],

    ],

    'preference_categories' => [

//        'category' => [
//            'name' => 'Category name', // (string) the name of the category
//        ],

    ],

];

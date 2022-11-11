<?php

return [

    'id' => null,

    'secret' => env('CHIEF_SECRET'),

    'auth' => [

        'model' => ChiefTools\SDK\Entities\User::class,

        'redirect' => '/dashboard',

        'passport' => env('CHIEF_PASSPORT_ENABLED', true),

    ],

    'brand' => [

        'icon' => 'fa-bug',

        'color' => '#34495e',

    ],

    'teams' => true,

    'queue' => [

        'monitor' => env('QUEUE_MONITOR_URL'),

    ],

    'guards' => [

        'api' => ['ctp', 'api'],

    ],

    'assets' => [

        'provider' => 'mix',

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

    'roadmap_url' => env('CHIEF_ROADMAP_URL', 'https://roadmap.chief.app'),

    'response' => [

        'securityheaders' => [

            'Referrer-Policy'           => 'strict-origin-when-cross-origin',
            'X-Frame-Options'           => 'SAMEORIGIN',
            'Permissions-Policy'        => 'accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=(), interest-cohort=()',
            'X-Content-Type-Options'    => 'nosniff',
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',

        ],

    ],

    'webhooks' => [

        'team_updated'    => ChiefTools\SDK\Webhook\Handlers\TeamUpdated::class,
        'team_destroyed'  => ChiefTools\SDK\Webhook\Handlers\TeamDestroyed::class,
        'account_closed'  => ChiefTools\SDK\Webhook\Handlers\AccountClosed::class,
        'account_updated' => ChiefTools\SDK\Webhook\Handlers\AccountUpdated::class,
        'token_destroyed' => ChiefTools\SDK\Webhook\Handlers\TokenDestroyed::class,

    ],

    'analytics' => [

        'fathom' => [

            'site' => env('ANALYTICS_FATHOM_SITE'),

            'domain' => env('ANALYTICS_FATHOM_DOMAIN', 'catfish.assets.chief.app'),

        ],

        'sentry' => [

            'public_dsn' => env('APP_DEBUG', false) ? null : env('SENTRY_PUBLIC_DSN', env('SENTRY_LARAVEL_DSN')),

        ],

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

    'home_route_resolver' => ChiefTools\SDK\Helpers\HomeRouteResolver::class,

];

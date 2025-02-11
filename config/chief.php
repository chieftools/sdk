<?php

return [

    'id' => null,

    'secret' => env('CHIEF_SECRET'),

    'auth' => [

        'account' => env('CHIEF_AUTH_ACCOUNT', true),

        'redirect' => '/dashboard',

        'passport' => env('CHIEF_PASSPORT_ENABLED', true),

    ],

    'brand' => [

        'icon' => 'fa-bug',

        'logoUrl' => null,

        'brandIcon' => null,

        'color' => '#34495e',

    ],

    'teams' => true,

    'queue' => [

        'monitor' => env('QUEUE_MONITOR_URL'),

    ],

    'guards' => [

        'api' => ['chief', 'chief_team'],

    ],

    'assets' => [

        'provider' => 'mix',

    ],

    'routes' => [

        'api' => [

            'domain'     => env('CHIEF_API_DOMAIN', env('APP_DOMAIN')),
            'prefix'     => 'api',
            'middleware' => ['api'],

        ],

        'web' => [

            'domain'     => env('CHIEF_WEB_DOMAIN', env('APP_DOMAIN')),
            'middleware' => ['web'],

        ],

        'web-api' => [

            'domain'     => env('CHIEF_WEB_API_DOMAIN', env('APP_DOMAIN')),
            'prefix'     => 'api',
            'middleware' => ['web', 'auth'],

        ],

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

    'docs_url' => env('CHIEF_DOCS_URL', 'https://docs.chief.tools'),

    'roadmap_url' => env('CHIEF_ROADMAP_URL', 'https://roadmap.chief.app'),

    'response' => [

        'securityheaders' => [

            'Referrer-Policy'                     => env('CHIEF_SECURITYHEADER_REFERRER_POLICY', 'strict-origin-when-cross-origin'),
            'X-Frame-Options'                     => env('CHIEF_SECURITYHEADER_X_FRAME_OPTIONS', 'SAMEORIGIN'),
            'X-XSS-Protection'                    => env('CHIEF_SECURITYHEADER_X_XSS_PROTECTION', '1; mode=block'),
            'Permissions-Policy'                  => env('CHIEF_SECURITYHEADER_PERMISSIONS_POLICY', 'accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()'),
            'X-Content-Type-Options'              => env('CHIEF_SECURITYHEADER_X_CONTENT_TYPE_OPTIONS', 'nosniff'),
            'Content-Security-Policy'             => env('CHIEF_SECURITYHEADER_CONTENT_SECURITY_POLICY'),
            'Strict-Transport-Security'           => env('CHIEF_SECURITYHEADER_STRICT_TRANSPORT_SECURITY', 'max-age=31536000; includeSubDomains; preload'),
            'Content-Security-Policy-Report-Only' => env('CHIEF_SECURITYHEADER_CONTENT_SECURITY_POLICY_REPORT_ONLY'),

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

            'public_tunnel' => env('SENTRY_PUBLIC_TUNNEL', 'https://visibility.on.chief.tools/ingest'),

            'traces' => [

                'sample_rate' => env('SENTRY_PUBLIC_TRACES_SAMPLE_RATE') === null ? null : (float)env('SENTRY_PUBLIC_TRACES_SAMPLE_RATE'),

            ],

            'replays' => [

                'sample_rate' => env('SENTRY_PUBLIC_REPLAYS_SAMPLE_RATE') === null ? null : (float)env('SENTRY_PUBLIC_REPLAYS_SAMPLE_RATE'),

                'error_sample_rate' => env('SENTRY_PUBLIC_REPLAYS_ERROR_SAMPLE_RATE') === null ? null : (float)env('SENTRY_PUBLIC_REPLAYS_ERROR_SAMPLE_RATE'),

            ],

        ],

    ],

    'preferences' => [

        // 'preference_key' => [
        //     'This is an example preference', // (string) Title of the preference
        //     'When enabled this example preference should do exactly nothing.', // (string) Longer description
        //     'hashtag', // (string) Font Awesome icon name
        //     false, // (bool) default value
        //     'category', // (string) reference to a preference category
        // ],

        // 'enable_support_widget' => [
        //     'Enable the support widget',
        //     'When enabled a support widget is shown in the bottom right corner of the screen where you can contact us anytime.',
        //     'comment',
        //     true,
        //     'ui',
        // ],

    ],

    'preference_categories' => [

        //        'category' => [
        //            'name' => 'Category name', // (string) the name of the category
        //        ],

        //        'ui' => [
        //            'name' => 'Interface',
        //        ],

    ],

    'home_route_resolver' => ChiefTools\SDK\Helpers\HomeRouteResolver::class,

    'og_generator' => [

        'secret' => env('CHIEF_OG_SECRET_KEY'),

    ],

];

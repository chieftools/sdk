<?php

return [

    'id' => null,

    'secret' => env('CHIEF_SECRET'),

    // The secret Account Chief signs its webhooks to this app with, the `whsec_` secret of the app's endpoint in Account Chief
    'webhook_signing_secret' => env('CHIEF_WEBHOOK_SIGNING_SECRET'),

    // Also accept Account Chief webhooks authenticated with the shared `services.chief.webhook_secret` header, turn
    // this off once the signing secret is set and Account Chief signs its webhooks
    'webhook_accept_legacy_secret' => env('CHIEF_WEBHOOK_ACCEPT_LEGACY_SECRET', true),

    'auth' => [

        'account' => env('CHIEF_AUTH_ACCOUNT', true),

        'redirect' => '/dashboard',

        'passport' => env('CHIEF_PASSPORT_ENABLED', true),

        'audiences' => [],

    ],

    'brand' => [

        'icon' => 'fa-bug',

        'logoUrl' => null,

        'brandIcon' => null,

        'color' => '#34495e',

    ],

    'shell' => [

        'theme' => 'system',

        'variant' => 'legacy',

        // `topbar` or `sidebar`, which needs the modern variant and moves the app's content right (on large screens)
        'layout' => 'topbar',

        // Lets each user choose the other layout, as a preference and in the account menu; `layout` stays the default.
        // Only for apps whose pages work in both layouts
        'layout_switcher' => false,

        'theme_cookie' => 'chief_shell_theme',

        'app_switcher' => true,

        'theme_selector' => true,

        'command_palette' => true,

        'theme_cookie_domain' => env('CHIEF_THEME_COOKIE_DOMAIN'),

        'command_palette_providers' => [],

    ],

    'teams' => true,

    /*
     * The global team view combines all teams of the user on the routes that allow it, with `->allowsGlobalTeam()` or
     * `'chief_global_team' => true` on their route group. Name the route the team switcher opens it on to enable it.
     */
    'global_team_home' => null,

    'queue' => [

        'monitor' => env('QUEUE_MONITOR_URL'),

    ],

    /*
     * Outbound webhooks the app sends to endpoints its teams configure, see `\ChiefTools\SDK\Webhook\Outbound\Webhooks`.
     * Apps that publish this config keep only the keys they set, the defaults live in `\ChiefTools\SDK\Webhook\Outbound\WebhookConfig`.
     */
    'webhooks' => [

        'enabled' => false,

        // The backed enum implementing `\ChiefTools\SDK\Webhook\Outbound\Contracts\WebhookEventType` with the app's events
        'events' => null,

        // The queue delivery jobs run on, null for the default queue
        'queue' => null,

        'api_version' => 'v1',

        // Defaults to "{App}-Webhooks/1.0"
        'user_agent' => null,

        'retention_days' => 30,

        // Seconds to wait after each failed attempt, the delivery fails after the last one
        'retry_schedule' => [60, 300, 1800, 7200, 21600, 43200, 86400, 86400],

        // Disable an endpoint after it only failed for this many days
        'disable_after_days' => 5,

        // Requests an endpoint gets at the same time and per second, so a slow or small receiver is not flooded and
        // cannot hold every queue worker. Exact with a cache store that counts atomically, like Redis, Memcached or the
        // database, the file and array stores can let an extra request through when workers count at the same moment
        'max_concurrent_per_endpoint' => 2,
        'max_per_second_per_endpoint' => 10,

        // Allows plain HTTP and private addresses, never enable this in production
        'allow_private_addresses' => env('CHIEF_WEBHOOKS_ALLOW_PRIVATE_ADDRESSES', false),

        // The app page the webhooks pages sit under in their breadcrumbs: `['title' => 'Settings', 'route' => 'settings']`
        'breadcrumb_parent' => null,

        // How IDs show in payloads and the dashboard, match the app's API: empty prefixes for plain ULIDs
        'id_prefixes' => [
            'endpoint' => 'webhook_',
            'event'    => 'event_',
            'delivery' => 'delivery_',
        ],

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

    'api_documentation' => [

        // Null keeps the current SDK behavior by following the API route setting.
        'enabled' => null,

        'rest' => [

            'documentation_url' => null,

            'documentation_route' => 'api.docs.rest',

            'playground_route' => null,

        ],

        'graphql' => [

            'documentation_url' => null,

            'documentation_route' => null,

            'playground_route' => 'api.playground',

        ],

    ],

    'mcp' => [

        // The URL MCP clients connect to, null uses the MCP audience. Apps without an MCP server have neither.
        'endpoint' => null,

        // Documentation on connecting MCP clients, linked from the developer menus and the API documentation.
        'documentation_url' => null,

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

    'pricing' => [

        'without_featured' => env('CHIEF_PRICING_WITHOUT_FEATURED', false),

    ],

    'app_home' => env('CHIEF_APP_HOME'),

    'base_url' => env('CHIEF_BASE_URL', 'https://account.chief.app'),

    'site_url' => env('CHIEF_SITE_URL', 'https://chief.app'),

    'docs_url' => env('CHIEF_DOCS_URL', 'https://docs.chief.tools'),

    'roadmap_url' => env('CHIEF_ROADMAP_URL', 'https://roadmap.chief.app'),

    'status_url' => env('CHIEF_STATUS_URL', 'https://status.chief.tools'),

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

    // Handlers of Account Chief webhooks, keyed by `\ChiefTools\SDK\Webhook\WebhookEvent` value, that replace or add to
    // the SDK's default handlers for team, account and token changes
    'webhook_handlers' => [],

    'analytics' => [

        'fathom' => [

            'site' => env('ANALYTICS_FATHOM_SITE'),

            'domain' => env('ANALYTICS_FATHOM_DOMAIN', 'catfish.assets.chief.app'),

        ],

        'sentry' => [

            'public_dsn' => env('APP_DEBUG', false) ? null : env('SENTRY_PUBLIC_DSN', env('SENTRY_LARAVEL_DSN')),

            'public_tunnel' => env('SENTRY_PUBLIC_TUNNEL', 'https://errors.chieftools.net/ingest'),

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

<?php

use GraphQL\Error\Debug;
use GraphQL\Validator\Rules\DisableIntrospection;
use IronGate\Integration\Exceptions\GraphQLHandler;
use Nuwave\Lighthouse\Execution\ExtensionErrorHandler;
use Nuwave\Lighthouse\Subscriptions\SubscriptionRouter;

$appNamespace = ucfirst(config('chief.namespace') ?? config('chief.id'));

return [

    /*
    |--------------------------------------------------------------------------
    | GraphQL endpoint
    |--------------------------------------------------------------------------
    |
    | Set the endpoint to which the GraphQL server responds.
    | The default route endpoint is "yourdomain.com/graphql".
    |
    */

    'route_name' => 'api/graphql',

    /*
    |--------------------------------------------------------------------------
    | Enable GET requests
    |--------------------------------------------------------------------------
    |
    | This setting controls if GET requests to the GraphQL endpoint are allowed.
    |
    */

    'route_enable_get' => true,

    /*
    |--------------------------------------------------------------------------
    | Route configuration
    |--------------------------------------------------------------------------
    |
    | Additional configuration for the route group.
    | Check options here https://lumen.laravel.com/docs/routing#route-groups
    |
    */

    'route' => false,

    /*
    |--------------------------------------------------------------------------
    | Schema declaration
    |--------------------------------------------------------------------------
    |
    | This is a path that points to where your GraphQL schema is located
    | relative to the app path. You should define your entire GraphQL
    | schema in this file (additional files may be imported).
    |
    */

    'schema' => [
        'register' => base_path('routes/graphql/schema.graphql'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Schema Cache
    |--------------------------------------------------------------------------
    |
    | A large part of the Schema generation is parsing into an AST.
    | This operation is pretty expensive so it is recommended to enable
    | caching in production mode.
    |
    */

    'cache' => [
        'enable' => env('LIGHTHOUSE_CACHE_ENABLE', !env('APP_DEBUG', false)),
        'key'    => env('LIGHTHOUSE_CACHE_KEY', 'lighthouse-schema:' . str_replace('.', '_', config('app.version'))),
        'ttl'    => env('LIGHTHOUSE_CACHE_TTL', 60 * 60 * 24 * 14),
    ],

    /*
    |--------------------------------------------------------------------------
    | Namespaces
    |--------------------------------------------------------------------------
    |
    | These are the default namespaces where Lighthouse looks for classes
    | that extend functionality of the schema.
    |
    */

    'namespaces' => [
        'models'        => "IronGate\\{$appNamespace}\\Models",
        'queries'       => "IronGate\\{$appNamespace}\\Http\\GraphQL\\Queries",
        'mutations'     => "IronGate\\{$appNamespace}\\Http\\GraphQL\\Mutations",
        'subscriptions' => "IronGate\\{$appNamespace}\\Http\\GraphQL\\Subscriptions",
        'interfaces'    => "IronGate\\{$appNamespace}\\Http\\GraphQL\\Interfaces",
        'unions'        => "IronGate\\{$appNamespace}\\Http\\GraphQL\\Unions",
        'scalars'       => "IronGate\\{$appNamespace}\\Http\\GraphQL\\Scalars",
        'directives'    => "IronGate\\{$appNamespace}\\Http\\GraphQL\\Directives",
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | Control how Lighthouse handles security related query validation.
    | This configures the options from http://webonyx.github.io/graphql-php/security/
    | A setting of "0" means that the validation rule is disabled.
    |
    */

    'security' => [
        'max_query_complexity'  => 0,
        'max_query_depth'       => 12,
        'disable_introspection' => DisableIntrospection::ENABLED,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Limits the maximum "count" that users may pass as an argument
    | to fields that are paginated with the @paginate directive.
    | A setting of "null" means the count is unrestricted.
    |
    */

    'paginate_max_count' => 100,

    /*
    |--------------------------------------------------------------------------
    | Pagination Amount Argument
    |--------------------------------------------------------------------------
    |
    | Set the name to use for the generated argument on paginated fields
    | that controls how many results are returned.
    | This will default to "first" in v4.
    |
    */

    'pagination_amount_argument' => 'first',

    /*
    |--------------------------------------------------------------------------
    | Debug
    |--------------------------------------------------------------------------
    |
    | Control the debug level as described in http://webonyx.github.io/graphql-php/error-handling/
    | Debugging is only applied if the global Laravel debug config is set to true.
    |
    */

    'debug' => Debug::INCLUDE_DEBUG_MESSAGE | Debug::INCLUDE_TRACE,

    /*
    |--------------------------------------------------------------------------
    | Error Handlers
    |--------------------------------------------------------------------------
    |
    | Register error handlers that receive the Errors that occur during execution
    | and handle them. You may use this to log, filter or format the errors.
    | The classes must implement \Nuwave\Lighthouse\Execution\ErrorHandler
    |
    */

    'error_handlers' => [
        ExtensionErrorHandler::class,
        GraphQLHandler::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | GraphQL Controller
    |--------------------------------------------------------------------------
    |
    | Specify which controller (and method) you want to handle GraphQL requests.
    |
    */

    'controller' => false,

    /*
    |--------------------------------------------------------------------------
    | Global ID
    |--------------------------------------------------------------------------
    |
    | When creating a GraphQL type that is Relay compliant, provide a named field
    | for the Node identifier.
    |
    */

    'global_id_field' => 'id',

    /*
    |--------------------------------------------------------------------------
    | Batched Queries
    |--------------------------------------------------------------------------
    |
    | GraphQL query batching means sending multiple queries to the server in one request,
    | You may set this flag to either process or deny batched queries.
    |
    */

    'batched_queries' => true,

    /*
    |--------------------------------------------------------------------------
    | Transactional Mutations
    |--------------------------------------------------------------------------
    |
    | Sets default setting for transactional mutations.
    | You may set this flag to have @create|@update mutations transactional or not.
    |
    */

    'transactional_mutations' => true,

    /*
    |--------------------------------------------------------------------------
    | GraphQL Subscriptions
    |--------------------------------------------------------------------------
    |
    | Here you can define GraphQL subscription "broadcasters" and "storage" drivers
    | as well their required configuration options.
    |
    */

    'subscriptions' => [

        /*
         * Determines if broadcasts should be queued by default.
         */

        'queue_broadcasts' => env('LIGHTHOUSE_QUEUE_BROADCASTS', true),

        /*
         * Default subscription storage.
         *
         * Any Laravel supported cache driver options are available here.
         */

        'storage' => env('LIGHTHOUSE_SUBSCRIPTION_STORAGE', 'redis'),

        /*
         * Default subscription broadcaster.
         */

        'broadcaster' => env('LIGHTHOUSE_BROADCASTER', 'pusher'),

        /*
         * Subscription broadcasting drivers with config options.
         */

        'broadcasters' => [
            'log' => [
                'driver' => 'log',
            ],

            'pusher' => [
                'driver'     => 'pusher',
                'routes'     => SubscriptionRouter::class . '@pusher',
                'connection' => env('BROADCAST_DRIVER'),
            ],
        ],

    ],

];

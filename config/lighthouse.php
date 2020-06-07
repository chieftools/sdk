<?php

use GraphQL\Error\Debug;
use GraphQL\Validator\Rules\DisableIntrospection;
use IronGate\Chief\GraphQL\Exceptions\GraphQLHandler;
use Nuwave\Lighthouse\Execution\ExtensionErrorHandler;

$appNamespace = ucfirst(config('chief.namespace') ?? config('chief.id'));

$prefixNamespace = config('chief.graphql.namespace.prefix', 'Http');
$prefixNamespace = empty($prefixNamespace) ? '' : "{$prefixNamespace}\\";

return [

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Controls the HTTP route that your GraphQL server responds to.
    | You may set `route` => false, to disable the default route
    | registration and take full control.
    |
    */

    'route' => false,

    /*
    |--------------------------------------------------------------------------
    | Schema Location
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
    | A large part of schema generation consists of parsing and AST manipulation.
    | This operation is very expensive, so it is highly recommended to enable
    | caching of the final schema to optimize performance of large schemas.
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
    | These are the default namespaces where Lighthouse looks for classes to
    | extend functionality of the schema. You may pass in either a string
    | or an array, they are tried in order and the first match is used.
    |
    */

    'namespaces' => [
        'models'        => "IronGate\\{$appNamespace}\\Models",
        'queries'       => "IronGate\\{$appNamespace}\\{$prefixNamespace}GraphQL\\Queries",
        'mutations'     => "IronGate\\{$appNamespace}\\{$prefixNamespace}GraphQL\\Mutations",
        'subscriptions' => "IronGate\\{$appNamespace}\\{$prefixNamespace}GraphQL\\Subscriptions",
        'interfaces'    => "IronGate\\{$appNamespace}\\{$prefixNamespace}GraphQL\\Interfaces",
        'unions'        => "IronGate\\{$appNamespace}\\{$prefixNamespace}GraphQL\\Unions",
        'scalars'       => "IronGate\\{$appNamespace}\\{$prefixNamespace}GraphQL\\Scalars",
        'directives'    => [
            "IronGate\\{$appNamespace}\\{$prefixNamespace}GraphQL\\Directives",
            'IronGate\\Chief\\GraphQL\\Directives',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | Control how Lighthouse handles security related query validation.
    | Read more at http://webonyx.github.io/graphql-php/security/
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
    |
    | DEPRECATED This setting will be removed in v5.
    |
    */

    'pagination_amount_argument' => 'first',

    /*
    |--------------------------------------------------------------------------
    | @orderBy input name
    |--------------------------------------------------------------------------
    |
    | Set the name to use for the generated argument on the
    | OrderByClause used for the @orderBy directive.
    |
    | DEPRECATED This setting will be removed in v5.
    |
    */

    'orderBy' => 'field',

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
    | Global ID
    |--------------------------------------------------------------------------
    |
    | The name that is used for the global id field on the Node interface.
    | When creating a Relay compliant server, this must be named "id".
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
    | If set to true, mutations such as @create or @update will be
    | wrapped in a transaction to ensure atomicity.
    |
    */

    'transactional_mutations' => true,

    /*
    |--------------------------------------------------------------------------
    | Batchload Relations
    |--------------------------------------------------------------------------
    |
    | If set to true, relations marked with directives like @hasMany or @belongsTo
    | will be optimized by combining the queries through the BatchLoader.
    |
    */

    'batchload_relations' => true,

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
         * Determines the queue to use for broadcasting queue jobs.
         */

        'broadcasts_queue_name' => env('LIGHTHOUSE_BROADCASTS_QUEUE_NAME', null),

        /*
         * Default subscription storage.
         *
         * Any Laravel supported cache driver options are available here.
         */

        'storage' => env('LIGHTHOUSE_SUBSCRIPTION_STORAGE', env('CACHE_DRIVER', 'redis')),

        /*
         * Default subscription storage time to live in seconds.
         *
         * Indicates how long a subscription can be active before it's automatically removed from storage.
         * Setting this to `null` means the subscriptions are stored forever. This may cause
         * stale subscriptions to linger indefinitely in case cleanup fails for any reason.
         */

        'storage_ttl' => env('LIGHTHOUSE_SUBSCRIPTION_STORAGE_TTL', 60 * 60),

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
                'connection' => env('BROADCAST_DRIVER'),
            ],

        ],

    ],

];

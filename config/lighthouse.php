<?php

use GraphQL\Error\Debug;
use GraphQL\Error\DebugFlag;
use GraphQL\Validator\Rules\QueryComplexity;
use GraphQL\Validator\Rules\DisableIntrospection;
use Nuwave\Lighthouse\Execution\ExtensionErrorHandler;
use Nuwave\Lighthouse\Execution\ReportingErrorHandler;

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
    | Authentication Guard
    |--------------------------------------------------------------------------
    |
    | The guard to use for authenticating GraphQL requests, if needed.
    | This setting is used whenever Lighthouse looks for an authenticated user, for example in directives
    | such as `@guard` and when applying the `AttemptAuthentication` middleware.
    |
    */

    'guard' => 'api',

    /*
    |--------------------------------------------------------------------------
    | Schema Location
    |--------------------------------------------------------------------------
    |
    | Path to your .graphql schema file.
    | Additional schema files may be imported from within that file.
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
        'store'  => env('LIGHTHOUSE_CACHE_STORE'),
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
        'directives'    => "IronGate\\{$appNamespace}\\{$prefixNamespace}GraphQL\\Directives",
        'validators'    => "IronGate\\{$appNamespace}\\{$prefixNamespace}GraphQL\\Validators",
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | Control how Lighthouse handles security related query validation.
    | Read more at https://webonyx.github.io/graphql-php/security/
    |
    */

    'security' => [
        'max_query_complexity'  => QueryComplexity::DISABLED,
        'max_query_depth'       => 12,
        'disable_introspection' => DisableIntrospection::ENABLED,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Set defaults for the pagination features within Lighthouse, such as
    | the @paginate directive, or paginated relation directives.
    |
    */

    'pagination' => [
        /*
         * Allow clients to query paginated lists without specifying the amount of items.
         * Setting this to `null` means clients have to explicitly ask for the count.
         */
        'default_count' => null,

        /*
         * Limit the maximum amount of items that clients can request from paginated lists.
         * Setting this to `null` means the count is unrestricted.
         */
        'max_count'     => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug
    |--------------------------------------------------------------------------
    |
    | Control the debug level as described in https://webonyx.github.io/graphql-php/error-handling/
    | Debugging is only applied if the global Laravel debug config is set to true.
    |
    | When you set this value through an environment variable, use the following reference table:
    |  0 => INCLUDE_NONE
    |  1 => INCLUDE_DEBUG_MESSAGE
    |  2 => INCLUDE_TRACE
    |  3 => INCLUDE_TRACE | INCLUDE_DEBUG_MESSAGE
    |  4 => RETHROW_INTERNAL_EXCEPTIONS
    |  5 => RETHROW_INTERNAL_EXCEPTIONS | INCLUDE_DEBUG_MESSAGE
    |  6 => RETHROW_INTERNAL_EXCEPTIONS | INCLUDE_TRACE
    |  7 => RETHROW_INTERNAL_EXCEPTIONS | INCLUDE_TRACE | INCLUDE_DEBUG_MESSAGE
    |  8 => RETHROW_UNSAFE_EXCEPTIONS
    |  9 => RETHROW_UNSAFE_EXCEPTIONS | INCLUDE_DEBUG_MESSAGE
    | 10 => RETHROW_UNSAFE_EXCEPTIONS | INCLUDE_TRACE
    | 11 => RETHROW_UNSAFE_EXCEPTIONS | INCLUDE_TRACE | INCLUDE_DEBUG_MESSAGE
    | 12 => RETHROW_UNSAFE_EXCEPTIONS | RETHROW_INTERNAL_EXCEPTIONS
    | 13 => RETHROW_UNSAFE_EXCEPTIONS | RETHROW_INTERNAL_EXCEPTIONS | INCLUDE_DEBUG_MESSAGE
    | 14 => RETHROW_UNSAFE_EXCEPTIONS | RETHROW_INTERNAL_EXCEPTIONS | INCLUDE_TRACE
    | 15 => RETHROW_UNSAFE_EXCEPTIONS | RETHROW_INTERNAL_EXCEPTIONS | INCLUDE_TRACE | INCLUDE_DEBUG_MESSAGE
    |
    */

    'debug' => env('LIGHTHOUSE_DEBUG', DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE),

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
        ReportingErrorHandler::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Middleware
    |--------------------------------------------------------------------------
    |
    | Register global field middleware directives that wrap around every field.
    | Execution happens in the defined order, before other field middleware.
    | The classes must implement \Nuwave\Lighthouse\Support\Contracts\FieldMiddleware
    |
    */

    'field_middleware' => [
        Nuwave\Lighthouse\Schema\Directives\TrimDirective::class,
        Nuwave\Lighthouse\Schema\Directives\SanitizeDirective::class,
        Nuwave\Lighthouse\Validation\ValidateDirective::class,
        Nuwave\Lighthouse\Schema\Directives\TransformArgsDirective::class,
        Nuwave\Lighthouse\Schema\Directives\SpreadDirective::class,
        Nuwave\Lighthouse\Schema\Directives\RenameArgsDirective::class,
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
    | Mass Assignment Protection
    |--------------------------------------------------------------------------
    |
    | If set to true, mutations will use forceFill() over fill() when populating
    | a model with arguments in mutation directives. Since GraphQL constrains
    | allowed inputs by design, mass assignment protection is not needed.
    |
    */

    'force_fill' => true,

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
    | Here you can define GraphQL subscription broadcaster and storage drivers
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

        /*
         * Controls the format of the extensions response.
         * Allowed values: 1, 2
         */

        'version' => env('LIGHTHOUSE_SUBSCRIPTION_VERSION', 2),

        /*
         * Should the subscriptions extension be excluded when the response has no subscription channel?
         * This optimizes performance by sending less data, but clients must anticipate this appropriately.
         * Will default to true in v6 and be removed in v7.
         */

        'exclude_empty' => env('LIGHTHOUSE_SUBSCRIPTION_EXCLUDE_EMPTY', true),

    ],

    /*
    |--------------------------------------------------------------------------
    | Defer
    |--------------------------------------------------------------------------
    |
    | Configuration for the experimental @defer directive support.
    |
    */

    'defer' => [

        /*
         * Maximum number of nested fields that can be deferred in one query.
         * Once reached, remaining fields will be resolved synchronously.
         * 0 means unlimited.
         */

        'max_nested_fields' => 0,

        /*
         * Maximum execution time for deferred queries in milliseconds.
         * Once reached, remaining fields will be resolved synchronously.
         * 0 means unlimited.
         */

        'max_execution_ms' => 0,

    ],

    /*
    |--------------------------------------------------------------------------
    | Apollo Federation
    |--------------------------------------------------------------------------
    |
    | Lighthouse can act as a federated service: https://www.apollographql.com/docs/federation/federation-spec.
    |
    */

    'federation' => [

        /*
         * Location of resolver classes when resolving the `_entities` field.
         */

        'entities_resolver_namespace' => "IronGate\\{$appNamespace}\\{$prefixNamespace}GraphQL\\Entities",

    ],
];

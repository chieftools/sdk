<?php

use GraphQL\Error\DebugFlag;
use GraphQL\Validator\Rules\QuerySecurityRule;

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
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | The guards to use for authenticating GraphQL requests, if needed.
    | Used in directives such as `@guard` or the `AttemptAuthentication` middleware.
    | Falls back to the Laravel default if `null`.
    |
    */

    'guards' => null,

    /*
    |--------------------------------------------------------------------------
    | Schema Path
    |--------------------------------------------------------------------------
    |
    | Path to your .graphql schema file.
    | Additional schema files may be imported from within that file.
    |
    */

    'schema_path' => base_path('routes/graphql/schema.graphql'),

    /*
    |--------------------------------------------------------------------------
    | Schema Cache
    |--------------------------------------------------------------------------
    |
    | A large part of schema generation consists of parsing and AST manipulation.
    | This operation is very expensive, so it is highly recommended enabling
    | caching of the final schema to optimize performance of large schemas.
    |
    */

    'schema_cache'         => [
        /*
         * Setting to true enables schema caching.
         */
        'enable' => env('LIGHTHOUSE_SCHEMA_CACHE_ENABLE', true),

        /*
         * File path to store the lighthouse schema.
         */
        'path'   => env('LIGHTHOUSE_SCHEMA_CACHE_PATH', base_path('bootstrap/cache/lighthouse-schema.php')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Directive Tags
    |--------------------------------------------------------------------------
    |
    | Should the `@cache` directive use a tagged cache?
    |
    */
    'cache_directive_tags' => false,

    /*
    |--------------------------------------------------------------------------
    | Query Cache
    |--------------------------------------------------------------------------
    |
    | Caches the result of parsing incoming query strings to boost performance on subsequent requests.
    |
    */

    'query_cache' => [
        /*
         * Setting to true enables query caching.
         */
        'enable' => env('LIGHTHOUSE_QUERY_CACHE_ENABLE', true),

        /*
         * Allows using a specific cache store, uses the app's default if set to null.
         */
        'store'  => env('LIGHTHOUSE_QUERY_CACHE_STORE', null),

        /*
         * Duration in seconds the query should remain cached, null means forever.
         */
        'ttl'    => env('LIGHTHOUSE_QUERY_CACHE_TTL', 24 * 60 * 60),
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
        'models'        => "ChiefTools\\{$appNamespace}\\Models",
        'queries'       => "ChiefTools\\{$appNamespace}\\{$prefixNamespace}GraphQL\\Queries",
        'mutations'     => "ChiefTools\\{$appNamespace}\\{$prefixNamespace}GraphQL\\Mutations",
        'subscriptions' => "ChiefTools\\{$appNamespace}\\{$prefixNamespace}GraphQL\\Subscriptions",
        'types'         => "ChiefTools\\{$appNamespace}\\{$prefixNamespace}GraphQL\\Types",
        'interfaces'    => "ChiefTools\\{$appNamespace}\\{$prefixNamespace}GraphQL\\Interfaces",
        'unions'        => "ChiefTools\\{$appNamespace}\\{$prefixNamespace}GraphQL\\Unions",
        'scalars'       => "ChiefTools\\{$appNamespace}\\{$prefixNamespace}GraphQL\\Scalars",
        'validators'    => "ChiefTools\\{$appNamespace}\\{$prefixNamespace}GraphQL\\Validators",
        'directives'    => [
            'ChiefTools\\SDK\\GraphQL\\Directives',
            "ChiefTools\\{$appNamespace}\\{$prefixNamespace}GraphQL\\Directives",
        ],
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
        'max_query_complexity'  => QuerySecurityRule::DISABLED,
        'max_query_depth'       => 14,
        'disable_introspection' => QuerySecurityRule::DISABLED,
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
        'default_count' => 10,

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
        Nuwave\Lighthouse\Execution\AuthenticationErrorHandler::class,
        Nuwave\Lighthouse\Execution\AuthorizationErrorHandler::class,
        Nuwave\Lighthouse\Execution\ValidationErrorHandler::class,
        Nuwave\Lighthouse\Execution\ReportingErrorHandler::class,
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
        // Nuwave\Lighthouse\Schema\Directives\ConvertEmptyStringsToNullDirective::class,
        Nuwave\Lighthouse\Schema\Directives\SanitizeDirective::class,
        Nuwave\Lighthouse\Validation\ValidateDirective::class,
        Nuwave\Lighthouse\Schema\Directives\TransformArgsDirective::class,
        Nuwave\Lighthouse\Schema\Directives\SpreadDirective::class,
        Nuwave\Lighthouse\Schema\Directives\RenameArgsDirective::class,
        // Nuwave\Lighthouse\Schema\Directives\DropArgsDirective::class,
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
    | Persisted Queries
    |--------------------------------------------------------------------------
    |
    | Lighthouse supports Automatic Persisted Queries (APQ), compatible with the
    | [Apollo implementation](https://www.apollographql.com/docs/apollo-server/performance/apq).
    | You may set this flag to either process or deny these queries.
    |
    */

    'persisted_queries' => false,

    /*
    |--------------------------------------------------------------------------
    | Transactional Mutations
    |--------------------------------------------------------------------------
    |
    | If set to true, built-in directives that mutate models will be
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
    | Shortcut Foreign Key Selection
    |--------------------------------------------------------------------------
    |
    | If set to true, Lighthouse will shortcut queries where the client selects only the
    | foreign key pointing to a related model. Only works if the related model's primary
    | key field is called exactly `id` for every type in your schema.
    |
    */

    'shortcut_foreign_key_selection' => false,

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
        'queue_broadcasts'      => env('LIGHTHOUSE_QUEUE_BROADCASTS', true),

        /*
         * Determines the queue to use for broadcasting queue jobs.
         */
        'broadcasts_queue_name' => env('LIGHTHOUSE_BROADCASTS_QUEUE_NAME'),

        /*
         * Default subscription storage.
         *
         * Any Laravel supported cache driver options are available here.
         */
        'storage'               => env('LIGHTHOUSE_SUBSCRIPTION_STORAGE', 'redis'),

        /*
         * Default subscription storage time to live in seconds.
         *
         * Indicates how long a subscription can be active before it's automatically removed from storage.
         * Setting this to `null` means the subscriptions are stored forever. This may cause
         * stale subscriptions to linger indefinitely in case cleanup fails for any reason.
         */
        'storage_ttl'           => env('LIGHTHOUSE_SUBSCRIPTION_STORAGE_TTL', 60 * 60),

        /*
         * Default subscription broadcaster.
         */
        'broadcaster'           => env('LIGHTHOUSE_BROADCASTER', 'pusher'),

        /*
         * Subscription broadcasting drivers with config options.
         */
        'broadcasters'          => [
            'log'    => [
                'driver' => 'log',
            ],
            'pusher' => [
                'driver'     => 'pusher',
                'connection' => env('BROADCAST_DRIVER'),
            ],
        ],

        /*
         * Should the subscriptions extension be excluded when the response has no subscription channel?
         * This optimizes performance by sending less data, but clients must anticipate this appropriately.
         */
        'exclude_empty'         => env('LIGHTHOUSE_SUBSCRIPTION_EXCLUDE_EMPTY', true),
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
        'max_execution_ms'  => 0,
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
        'entities_resolver_namespace' => "ChiefTools\\{$appNamespace}\\{$prefixNamespace}GraphQL\\Entities",
    ],

];

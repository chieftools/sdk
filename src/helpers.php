<?php

use Illuminate\Support\Carbon;

function home(): string
{
    $resolver = config('chief.home_route_resolver');

    return (new $resolver)(request());
}

/**
 * Get active state based on whitelist.
 * Used to indicate active menu's.
 *
 * @param string|array $whitelist
 * @param string|array $blacklist
 * @param mixed        $active
 * @param mixed        $inactive
 *
 * @return mixed
 */
function active($whitelist = null, $blacklist = null, $active = 'active', $inactive = '')
{
    $whitelisted = false;
    $blacklisted = false;

    // Match against the whitelist, if any
    if (!empty($whitelist)) {
        foreach ((array)$whitelist as $item) {
            if (request()->is($item)) {
                $whitelisted = true;

                break;
            }
        }
    }

    // Match against the blacklist, if any
    if (!empty($blacklist)) {
        $blacklisted = false;

        foreach ((array)$blacklist as $item) {
            if (request()->is($item)) {
                $blacklisted = true;

                break;
            }
        }
    }

    // If there was nu whitelist, only check if we are not blacklisted
    if (empty($whitelist)) {
        return (!$blacklisted) ? $active : $inactive;
    }

    return ($whitelisted && !$blacklisted) ? $active : $inactive;
}

/**
 * Return an key-value list of all timezones.
 *
 * @return array
 */
function timezones(): array
{
    $timezones = DateTimeZone::listIdentifiers();

    return array_combine($timezones, $timezones);
}

/**
 * Retrieve a time that is now and in the users timezone.
 *
 * @return \Illuminate\Support\Carbon
 */
function user_now(): Carbon
{
    return now()->setTimezone(config('app.timezone_user'));
}

/**
 * Sync the authenticated user timezone to the correct config key.
 *
 * @param \ChiefTools\SDK\Entities\User|\ChiefTools\SDK\Entities\Team|null $user
 */
function sync_user_timezone(ChiefTools\SDK\Entities\User|ChiefTools\SDK\Entities\Team|null $user = null): void
{
    $user = $user ?? authenticated_user();

    config([
        'app.timezone_user' => $user->timezone ?? null,
    ]);
}

/**
 * Validate some data.
 */
function validate(mixed $fields, string|array|Illuminate\Contracts\Validation\Rule $rules): bool
{
    if (!is_array($fields)) {
        $fields = ['field' => $fields];
        $rules  = ['field' => $rules];
    }

    return Illuminate\Support\Facades\Validator::make($fields, $rules)->passes();
}

/**
 * Retrieve apps except the current.
 *
 * @param bool|null $authenticated
 * @param bool      $cached
 *
 * @throws \Exception
 *
 * @return \Illuminate\Support\Collection|null
 */
function chief_apps(?bool $authenticated = null, bool $cached = true): ?Illuminate\Support\Collection
{
    $retriever = function () use ($authenticated) {
        /** @var \ChiefTools\SDK\API\Client $api */
        $api = app(ChiefTools\SDK\API\Client::class);

        // Retrieve all apps (except the current) that require authentication
        return $api->apps(config('chief.id'), null, $authenticated);
    };

    return rescue(function () use ($cached, $authenticated, $retriever) {
        $authenticatedCacheKeys = [null => 'all', true => 'authenticated', false => 'unauthenticated'];

        $cacheKey = 'chief:apps:' . $authenticatedCacheKeys[$authenticated];

        return $cached
            ? cache()->remember($cacheKey, now()->addHours(12), $retriever)
            : $retriever();
    });
}

/**
 * Get the Chief account manager base url.
 *
 * @param string|null $path
 *
 * @return string
 */
function chief_base_url(?string $path = null): string
{
    $base = rtrim(config('chief.base_url'), '/');
    $path = $path === null ? '' : ltrim($path, '/');

    return "{$base}/{$path}";
}

/**
 * Get the global Chief site url.
 *
 * @param string|null $path
 *
 * @return string
 */
function chief_site_url(?string $path = null): string
{
    $base = rtrim(config('chief.site_url'), '/');
    $path = $path === null ? '' : ltrim($path, '/');

    return "{$base}/{$path}?ref=" . config('chief.id');
}

/**
 * Get the url to the Chief docs site for the current application.
 *
 * @return string
 */
function chief_docs_url(): string
{
    $base  = rtrim(config('chief.docs_url'), '/');
    $appId = config('chief.id');

    if (empty($appId)) {
        return $base;
    }

    return "{$base}/{$appId}";
}

/**
 * Get the url to the Chief roadmap site for the current application.
 *
 * @return string
 */
function chief_roadmap_url(): string
{
    $base  = rtrim(config('chief.roadmap_url'), '/');
    $appId = config('chief.id');

    if (empty($appId)) {
        return $base;
    }

    return "{$base}/projects/{$appId}";
}

/**
 * Check if we can reach the outside internet.
 *
 * @return bool
 */
function outside_reachable(): bool
{
    $connected = @fsockopen('www.google.com', 443);

    if ($connected) {
        @fclose($connected);

        return true;
    }

    return false;
}

/**
 * Get the path to the most up-to-date CA bundle file.
 *
 * @return string
 */
function latest_ca_bundle_file_path(): string
{
    return ChiefTools\SDK\ServiceProvider::basePath('files/cacert-2024-07-02.pem');
}

/**
 * Dispatch a GraphQL subscription.
 *
 * @param string    $subscription
 * @param mixed     $root
 * @param bool|null $shouldQueue
 */
function dispatch_subscription(string $subscription, mixed $root, ?bool $shouldQueue = null): void
{
    if (!config('chief.graphql.subscriptions.enabled')) {
        return;
    }

    // Try and be clever to get a subscription name for a subscription class name
    if (class_exists($subscription)) {
        if (defined($nameConstant = "{$subscription}::SUBSCRIPTION_NAME")) {
            $subscription = constant($nameConstant);
        } else {
            $subscription = lcfirst(class_basename($subscription));
        }
    }

    // We check if the subscription has any subscribers (which is a really cheap operation) before dispatching a job that we know to do nothing
    // The check can return `null` so we need to check for `false` explicitly to ensure we only skip if we are certain about the result
    if (does_subscription_have_subscribers($subscription, $root) === false) {
        logger()->debug("Skipping subscription:{$subscription} because there are no subscribers");

        return;
    }

    // Optimize the queued job by unsetting all relations so they are not automatically loaded
    if ($root instanceof Illuminate\Database\Eloquent\Model) {
        $root = $root->withoutRelations();
    }

    logger()->debug("Dispatching subscription:{$subscription}");

    Nuwave\Lighthouse\Execution\Utils\Subscription::broadcast($subscription, $root, $shouldQueue);
}

/**
 * Test if a GraphQL subscription has any subscribers.
 *
 * @param string $subscriptionField
 * @param mixed  $root
 *
 * @return bool|null
 */
function does_subscription_have_subscribers(string $subscriptionField, mixed $root): ?bool
{
    $subscriptionStorage = app(Nuwave\Lighthouse\Subscriptions\Contracts\StoresSubscriptions::class);

    // We only support this test for our own storage manager that has the needed API to access the information
    if (!$subscriptionStorage instanceof ChiefTools\SDK\GraphQL\Subscriptions\Storage\RedisStorageManager) {
        return null;
    }

    // We need to load the schema into memory to be able to access the subscription registry correctly
    app(Nuwave\Lighthouse\Schema\SchemaBuilder::class)->schema();

    $registry = app(Nuwave\Lighthouse\Subscriptions\SubscriptionRegistry::class);

    // If the subscription field cannot be found it's gonna throw when we dispatch, let the error come from there instead of here
    if (!$registry->has($subscriptionField)) {
        return null;
    }

    $topic = $registry->subscription($subscriptionField)->decodeTopic($subscriptionField, $root);

    return $subscriptionStorage->hasSubscribersForTopic($topic);
}

/**
 * Get the user agent for the application.
 *
 * @return string
 */
function user_agent(): string
{
    return sprintf(
        '%sBot/%s (+https://aka.chief.app/bot)',
        str_replace(' ', '', config('app.name')),
        config('app.version'),
    );
}

/**
 * Get the user agent for the application used for internal requests.
 *
 * @return string
 */
function internal_user_agent(): string
{
    return sprintf(
        '%s/%s',
        str_replace(' ', '', config('app.name')),
        config('app.version'),
    );
}

/**
 * Get the user agent for the application used for crawling.
 *
 * @return string
 */
function crawler_user_agent(): string
{
    return sprintf(
        'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; %sBot/%s; +https://aka.chief.app/bot) Chrome/123.0.6312.58 Safari/537.36',
        str_replace(' ', '', config('app.name')),
        config('app.version'),
    );
}

/**
 * Get a HTTP client to use with sane timeouts and defaults.
 *
 * @param string|null   $baseUri
 * @param array         $headers
 * @param int           $timeout
 * @param array         $options
 * @param \Closure|null $stackCallback
 *
 * @return \GuzzleHttp\Client
 */
function http(?string $baseUri = null, array $headers = [], int $timeout = 10, array $options = [], ?Closure $stackCallback = null): GuzzleHttp\Client
{
    $stack = GuzzleHttp\HandlerStack::create();

    if (app()->bound(Sentry\State\HubInterface::class)) {
        $stack->push(Sentry\Tracing\GuzzleTracingMiddleware::trace());
    }

    if ($stackCallback !== null) {
        $stackCallback($stack);
    }

    return new GuzzleHttp\Client(array_merge($options, [
        'base_uri'        => $baseUri,
        'handler'         => $stack,
        'timeout'         => $timeout,
        'connect_timeout' => $timeout,
        'headers'         => array_merge([
            'User-Agent' => user_agent(),
        ], $headers, $options['headers'] ?? []),
    ]));
}

/**
 * Get a HTTP client to use with sane timeouts and defaults used for crawling.
 *
 * @param string|null   $baseUri
 * @param array         $headers
 * @param int           $timeout
 * @param array         $options
 * @param \Closure|null $stackCallback
 *
 * @return \GuzzleHttp\Client
 */
function crawler_http(?string $baseUri = null, array $headers = [], int $timeout = 10, array $options = [], ?Closure $stackCallback = null): GuzzleHttp\Client
{
    return http($baseUri, array_merge([
        'User-Agent' => crawler_user_agent(),
    ], $headers), $timeout, $options, $stackCallback);
}

/**
 * Get a HTTP client to use with sane timeouts and defaults used for internal use.
 *
 * @param string|null   $baseUri
 * @param array         $headers
 * @param int           $timeout
 * @param array         $options
 * @param \Closure|null $stackCallback
 *
 * @return \GuzzleHttp\Client
 */
function internal_http(?string $baseUri = null, array $headers = [], int $timeout = 10, array $options = [], ?Closure $stackCallback = null): GuzzleHttp\Client
{
    return http(
        $baseUri,
        array_merge([
            'User-Agent' => internal_user_agent(),
        ], $headers),
        $timeout,
        array_merge([
            'verify' => config('services.chief.verify', true),
        ], $options),
        $stackCallback,
    );
}

/**
 * Return the URL to a static asset hosted on a global CDN.
 *
 * @param string|null $path
 *
 * @return string
 */
function static_asset(?string $path = null): string
{
    $url = 'https://static.assets.chief.tools';

    if (!empty($path)) {
        $url .= '/' . ltrim($path, '/');
    }

    return $url;
}

/**
 * Encode an array specially to be parsed by JS.
 *
 * @param array $data
 *
 * @return string
 */
function js_json_encode(array $data): string
{
    return str_replace(["\u0022", "\u0027"], ['\\\\"', "\\'"], json_encode($data, JSON_HEX_QUOT | JSON_HEX_APOS | JSON_THROW_ON_ERROR));
}

/**
 * Executed the $with closure while the paginator current page resolver is set to $resolver.
 *
 * @param \Closure $with
 * @param \Closure $resolver
 *
 * @return mixed
 */
function with_custom_pagination_resolver(Closure $with, Closure $resolver): mixed
{
    $resolverProperty = (new ReflectionClass(Illuminate\Pagination\Paginator::class))->getProperty('currentPageResolver');
    $resolverProperty->setAccessible(true);

    $currentResolver = $resolverProperty->getValue();

    Illuminate\Pagination\Paginator::currentPageResolver($resolver);

    $return = $with();

    Illuminate\Pagination\Paginator::currentPageResolver($currentResolver);

    return $return;
}

/**
 * Convert enum to it's value.
 *
 * @param \UnitEnum|null $enum
 *
 * @return string|int|null
 */
function enum_value(?UnitEnum $enum): string|int|null
{
    if ($enum === null) {
        return null;
    }

    if ($enum instanceof BackedEnum) {
        return $enum->value;
    }

    return $enum->name;
}

/**
 * Capture an exception to Sentry if the client is registered.
 *
 * @param \Throwable    $throwable
 * @param callable|null $contextCallback
 *
 * @phpstan-param callable(Sentry\State\Scope $scope): void|null $contextCallback
 *
 * @return string|null
 */
function capture_exception_to_sentry(Throwable $throwable, ?callable $contextCallback = null): ?string
{
    if (app()->bound(Sentry\State\HubInterface::class)) {
        $sentry = app(Sentry\State\HubInterface::class);

        $eventId = null;

        $sentry->withScope(function (Sentry\State\Scope $scope) use (&$eventId, $contextCallback, $sentry, $throwable) {
            if ($contextCallback !== null) {
                $contextCallback($scope);
            }

            $eventId = $sentry->captureException($throwable);
        });

        if ($eventId instanceof Sentry\EventId) {
            return (string)$eventId;
        }
    }

    return null;
}

/**
 * Access a private/protected class property from an object instance.
 *
 * @param object $object
 * @param string $propertyName
 *
 * @throws \ReflectionException
 *
 * @return mixed
 */
function __access_class_property(object $object, string $propertyName): mixed
{
    $reflection = new ReflectionClass($object);

    $property = $reflection->getProperty($propertyName);

    $property->setAccessible(true);

    return $property->getValue($object);
}

/**
 * Update a private/protected class property from an object instance.
 *
 * @param object $object
 * @param string $propertyName
 * @param mixed  $value
 *
 * @throws \ReflectionException
 */
function __set_class_property(object $object, string $propertyName, mixed $value): void
{
    $reflection = new ReflectionClass($object);

    $property = $reflection->getProperty($propertyName);

    $property->setAccessible(true);

    $property->setValue($object, $value);
}

function authenticated_user(): ?ChiefTools\SDK\Entities\User
{
    /** @var \ChiefTools\SDK\Entities\User|null $user */
    $user = auth()->user();

    return $user;
}

function authenticated_user_or_fail(): ChiefTools\SDK\Entities\User
{
    $user = authenticated_user();

    if ($user === null) {
        throw new RuntimeException('User is not authenticated.');
    }

    return $user;
}

function base64encode_urlsafe(string $input): string
{
    return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
}

function base64decode_urlsafe(string $input): string
{
    return base64_decode(str_pad(strtr($input, '-_', '+/'), strlen($input) % 4, '='));
}

function generate_og_image_url(array $params, string $slug, string $format = 'png', ?int $timestamp = null): Illuminate\Support\HtmlString
{
    $secretKey = config('chief.og_generator.secret');

    if (empty($secretKey)) {
        throw new RuntimeException('Missing OG image secret key.');
    }

    $encodedParams = base64encode_urlsafe(json_encode($params, JSON_THROW_ON_ERROR));

    $signature = base64encode_urlsafe(hash_hmac('sha256', $encodedParams, $secretKey, true));

    $queryParams = http_build_query(array_filter([
        'signature' => $signature,
        'v'         => $timestamp,
    ]));

    return new Illuminate\Support\HtmlString(
        static_asset(
            sprintf('/og/%s/%s/%s.%s?%s', config('chief.id'), $encodedParams, $slug, $format, $queryParams),
        ),
    );
}

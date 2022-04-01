<?php

use Illuminate\Support\Carbon;

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
 * @param \IronGate\Chief\Entities\User|null $user
 */
function sync_user_timezone(?IronGate\Chief\Entities\User $user = null): void
{
    $user = $user ?? auth()->user();

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
        /** @var \IronGate\Chief\API\Client $api */
        $api = app(IronGate\Chief\API\Client::class);

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

    return "{$base}/{$path}";
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
    $certaintyPath = rescue(function () {
        /** @var \ParagonIE\Certainty\RemoteFetch $fetch */
        $fetch = app(ParagonIE\Certainty\RemoteFetch::class);

        return $fetch->getLatestBundle()->getFilePath();
    }, null, false);

    return $certaintyPath ?? resource_path('files/cacert-2022-02-01.pem');
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
        $subscription = lcfirst(class_basename($subscription));
    }

    logger()?->debug("Dispatching subscription:{$subscription}");

    Nuwave\Lighthouse\Execution\Utils\Subscription::broadcast($subscription, $root, $shouldQueue);
}

/**
 * Dispatch a job but retry it a few times in case SQS has issues.
 *
 * @param mixed $job
 *
 * @throws \Exception
 */
function dispatch_vapor(mixed $job): void
{
    retry(10, static fn () => dispatch($job), 200);
}

/**
 * Get the user agent for the application.
 *
 * @return string
 */
function user_agent(): string
{
    return sprintf(
        '%s/%s (+%s)',
        str_replace(' ', '', config('app.name')),
        config('app.version'),
        config('chief.app_home') ?? url('/')
    );
}

/**
 * Get an HTTP client to use with sane timeouts and defaults.
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

    if (app()->bound('sentry')) {
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
        'headers'         => array_merge($headers, [
            'User-Agent' => user_agent(),
        ]),
    ]));
}

/**
 * Replace the Vapor asset domain with a custom asset domain.
 *
 * This should only be called from the `config/app.php` file.
 *
 * @param string|null $assetUrl
 *
 * @return string|null
 *
 * @noinspection LaravelFunctionsInspection
 */
function replace_custom_asset_domain(?string $assetUrl): ?string
{
    if ($assetUrl === null) {
        return $assetUrl;
    }

    $plainDomain  = env('VAPOR_ASSET_DOMAIN');
    $customDomain = env('VAPOR_CUSTOM_ASSET_DOMAIN');

    if ($plainDomain === null || $customDomain === null) {
        return $assetUrl;
    }

    return str_replace($plainDomain, $customDomain, $assetUrl);
}

/**
 * Check if we are currently running on Laravel Vapor.
 *
 * @return bool
 */
function is_running_on_vapor(): bool
{
    return isset($_SERVER['VAPOR_ARTIFACT_NAME']);
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
    $url = 'https://static.assets.chief.app';

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

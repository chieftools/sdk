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
 */
function sync_user_timezone(): void
{
    config([
        'app.timezone_user' => auth()->check() ? auth()->user()->timezone : null,
    ]);
}

/**
 * Validate some data.
 *
 * @param string|array $fields
 * @param string|array $rules
 *
 * @return bool
 */
function validate($fields, $rules): bool
{
    if (!is_array($fields)) {
        $fields = ['default' => $fields];
    }

    if (!is_array($rules)) {
        $rules = ['default' => $rules];
    }

    return Validator::make($fields, $rules)->passes();
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

    return $certaintyPath ?? resource_path('files/cacert-2019-08-28.pem');
}

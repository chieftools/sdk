<?php

use Illuminate\Support\Carbon;

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
 * Retrieve apps except the current.
 *
 * @param bool|null $authenticated
 * @param bool      $cached
 *
 * @throws \Exception
 * @return \Illuminate\Support\Collection|null
 */
function chief_apps(?bool $authenticated = null, bool $cached = true): ?Illuminate\Support\Collection
{
    $retriever = function () use ($authenticated) {
        /** @var \IronGate\Integration\API\Client $api */
        $api = app(IronGate\Integration\API\Client::class);

        // Retrieve all apps (except the current) that require authentication
        return $api->apps(config('chief.id'), null, $authenticated);
    };

    return rescue(function () use ($cached, $authenticated, $retriever) {
        $authenticatedCacheKeys = [null => 'all', true => 'authenticated', false => 'unauthenticated'];

        $cacheKey = 'chief:apps:' . $authenticatedCacheKeys[$authenticated];

        return $cached
            ? cache()->tags('chief:apps')->remember($cacheKey, now()->addHours(12), $retriever)
            : $retriever();
    });
}

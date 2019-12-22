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
            ? cache()->remember($cacheKey, now()->addHours(12), $retriever)
            : $retriever();
    });
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

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

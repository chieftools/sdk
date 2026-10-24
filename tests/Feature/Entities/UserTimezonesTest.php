<?php

use Illuminate\Support\Carbon;
use Tests\Fixtures\Models\User;

it('stores a date without changing the instance it was given', function () {
    config(['app.timezone_user' => 'Europe/Amsterdam']);

    $value = Carbon::parse('2026-10-24 18:00:00', 'UTC');

    expect((new User)->fromDateTime($value))->toBe('2026-10-24 18:00:00')
        ->and($value->toIso8601String())->toBe('2026-10-24T18:00:00+00:00');
});

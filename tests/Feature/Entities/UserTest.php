<?php

use Tests\Fixtures\Models\User;
use ChiefTools\SDK\Socialite\ChiefUser;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('updates existing users through the model that initiated the remote sync', function () {
    $chiefId = 'ac2822d4-b112-4e36-810f-609be21af8dc';
    $user    = new User;
    $user->forceFill([
        'chief_id' => $chiefId,
        'name'     => 'Jordan Finch',
        'email'    => 'jordan@finch.example',
        'password' => 'synthetic-password',
        'timezone' => 'Europe/Amsterdam',
    ])->save();

    User::$updatedChiefIds = [];

    $updatedUser = User::createOrUpdateFromRemote(new ChiefUser([
        'id'              => $chiefId,
        'name'            => 'Jordan Finch Updated',
        'email'           => 'jordan@finch.example',
        'timezone'        => 'Europe/London',
        'avatar_hash'     => 'finch47',
        'default_team_id' => null,
        'teams'           => [],
    ]));

    expect($updatedUser)->toBeInstanceOf(User::class)
        ->and($updatedUser->name)->toBe('Jordan Finch Updated')
        ->and(User::$updatedChiefIds)->toBe([$chiefId]);
});

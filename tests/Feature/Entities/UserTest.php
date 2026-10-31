<?php

use Tests\Fixtures\Models\User;
use ChiefTools\SDK\Socialite\ChiefUser;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

it('uses the team from an exact team header', function () {
    $user = createTeamContextUser();
    createTeamContextTeam('amber-grove', $user);
    $team = createTeamContextTeam('birch-hollow', $user);

    expect($user->currentTeam(teamContextRequest('birch-hollow'))->is($team))->toBeTrue();
});

it('rejects a team header that does not match one of the user teams', function () {
    $user = createTeamContextUser();
    createTeamContextTeam('amber-grove', $user);
    createTeamContextTeam('birch-hollow');

    $user->currentTeam(teamContextRequest('birch-hollow'));
})->throws(NotFoundHttpException::class, 'The team from the X-Chief-Team header was not found.');

it('uses the first team for access token requests without a team hint', function () {
    $user = createTeamContextUser()->withChiefRemoteAccessToken(teamContextAccessToken());
    $team = createTeamContextTeam('amber-grove', $user);
    createTeamContextTeam('birch-hollow', $user);

    expect($user->currentTeam(teamContextRequest())->is($team))->toBeTrue();
});

it('lets admins use any team from the team header in the apps', function () {
    $user = createTeamContextUser(isAdmin: true);
    createTeamContextTeam('amber-grove', $user);
    $team = createTeamContextTeam('birch-hollow');

    expect($user->currentTeam(teamContextRequest('birch-hollow'))->is($team))->toBeTrue();
});

it('limits admin access tokens to the teams the admin is a member of', function () {
    $user = createTeamContextUser(isAdmin: true)->withChiefRemoteAccessToken(teamContextAccessToken());
    createTeamContextTeam('amber-grove', $user);
    createTeamContextTeam('birch-hollow');

    $user->currentTeam(teamContextRequest('birch-hollow'));
})->throws(NotFoundHttpException::class, 'The team from the X-Chief-Team header was not found.');

it('uses the team of a team limited access token instead of the first team without a team hint', function () {
    $user    = createTeamContextUser();
    createTeamContextTeam('amber-grove', $user);
    $limited = createTeamContextTeam('birch-hollow', $user);

    $user->withChiefRemoteAccessToken(teamContextAccessToken(teamId: $limited->id));

    expect($user->currentTeam(teamContextRequest())->is($limited))->toBeTrue();
});

it('rejects a team header outside the team of a team limited access token', function () {
    $user    = createTeamContextUser();
    $limited = createTeamContextTeam('amber-grove', $user);
    createTeamContextTeam('birch-hollow', $user);

    $user->withChiefRemoteAccessToken(teamContextAccessToken(teamId: $limited->id));

    $user->currentTeam(teamContextRequest('birch-hollow'));
})->throws(NotFoundHttpException::class, 'The team from the X-Chief-Team header was not found.');

it('limits the accessible teams to the team of a team limited access token', function () {
    $user    = createTeamContextUser();
    $limited = createTeamContextTeam('amber-grove', $user);
    createTeamContextTeam('birch-hollow', $user);

    expect($user->accessibleTeams()->pluck('slug')->all())->toBe(['amber-grove', 'birch-hollow']);

    $user->withChiefRemoteAccessToken(teamContextAccessToken(teamId: $limited->id));

    expect($user->accessibleTeams()->pluck('slug')->all())->toBe(['amber-grove']);
});

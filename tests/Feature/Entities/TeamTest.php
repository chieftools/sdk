<?php

use ChiefTools\SDK\Entities\Team;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('resolves any team from the route for admins in the apps', function () {
    $user = createTeamContextUser(isAdmin: true);
    createTeamContextTeam('amber-grove', $user);
    $team = createTeamContextTeam('birch-hollow');

    $this->actingAs($user);

    expect((new Team)->resolveRouteBinding('birch-hollow')?->is($team))->toBeTrue();
});

it('does not resolve teams from the route outside the memberships of admin access tokens', function () {
    $user = createTeamContextUser(isAdmin: true)->withChiefRemoteAccessToken(teamContextAccessToken());
    createTeamContextTeam('amber-grove', $user);
    createTeamContextTeam('birch-hollow');

    $this->actingAs($user);

    expect((new Team)->resolveRouteBinding('birch-hollow'))->toBeNull();
});

it('does not resolve teams from the route outside the team of a team limited access token', function () {
    $user    = createTeamContextUser();
    $limited = createTeamContextTeam('amber-grove', $user);
    createTeamContextTeam('birch-hollow', $user);

    $user->withChiefRemoteAccessToken(teamContextAccessToken(teamId: $limited->id));

    $this->actingAs($user);

    expect((new Team)->resolveRouteBinding('birch-hollow'))->toBeNull()
        ->and((new Team)->resolveRouteBinding('amber-grove')?->is($limited))->toBeTrue();
});

it('treats a membership as restricted from the raw database value without a boolean cast on the pivot model', function (mixed $value, bool $restricted) {
    $team = (new Team)->setRelation('pivot', (new ChiefTools\SDK\Entities\PivotEntity)->setRawAttributes(['access_restricted' => $value]));

    expect($team->isAccessRestricted())->toBe($restricted);
})->with([
    'restricted'     => [1, true],
    'not restricted' => [0, false],
    'unknown'        => [null, false],
]);

it('never treats a team loaded without a membership as restricted', function () {
    expect((new Team)->isAccessRestricted())->toBeFalse();
});

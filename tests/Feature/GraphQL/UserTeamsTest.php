<?php

use Illuminate\Http\Request;
use Tests\Fixtures\Models\User;
use ChiefTools\SDK\GraphQL\Context;
use GraphQL\Type\Definition\ResolveInfo;
use ChiefTools\SDK\GraphQL\Resolvers\UserTeams;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function resolveUserTeamsFor(User $user): array
{
    $request = Request::create('/api/graphql');
    $request->setUserResolver(static fn () => $user);

    return app(UserTeams::class)($user, [], new Context($request), test()->createStub(ResolveInfo::class))->pluck('slug')->all();
}

it('resolves all teams of the user for a token without a team limit', function () {
    $user = createTeamContextUser()->withChiefRemoteAccessToken(teamContextAccessToken());
    createTeamContextTeam('amber-grove', $user);
    createTeamContextTeam('birch-hollow', $user);
    createTeamContextTeam('cedar-ridge');

    expect(resolveUserTeamsFor($user))->toBe(['amber-grove', 'birch-hollow']);
});

it('resolves only the team a token is limited to', function () {
    $user    = createTeamContextUser();
    createTeamContextTeam('amber-grove', $user);
    $limited = createTeamContextTeam('birch-hollow', $user);

    $user->withChiefRemoteAccessToken(teamContextAccessToken(teamId: $limited->id));

    expect(resolveUserTeamsFor($user))->toBe(['birch-hollow']);
});

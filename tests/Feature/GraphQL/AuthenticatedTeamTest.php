<?php

use Carbon\Carbon;
use Illuminate\Http\Request;
use Tests\Fixtures\Models\User;
use ChiefTools\SDK\GraphQL\Context;
use GraphQL\Type\Definition\ResolveInfo;
use ChiefTools\SDK\Auth\ChiefRemoteAccessToken;
use ChiefTools\SDK\GraphQL\Resolvers\AuthenticatedTeam;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function resolveAuthenticatedTeamFor(User $user, ?string $hint): mixed
{
    auth()->setUser($user);

    $request = Request::create('/api/graphql');
    $request->setUserResolver(static fn () => $user);

    return app(AuthenticatedTeam::class)(null, ['hint' => $hint], new Context($request), test()->createStub(ResolveInfo::class));
}

function authenticatedTeamToken(User $user, ?int $teamId): ChiefRemoteAccessToken
{
    return new ChiefRemoteAccessToken(
        id: 'synthetic-token',
        name: 'Synthetic token',
        prefix: 'ctp',
        scopes: [],
        userId: $user->chief_id,
        teamId: $teamId,
        expiresAt: Carbon::now()->addDay(),
    );
}

it('resolves a hinted team the user belongs to', function () {
    $user   = createTeamContextUser();
    createTeamContextTeam('amber-grove', $user);
    $hinted = createTeamContextTeam('birch-hollow', $user);

    $user->withChiefRemoteAccessToken(authenticatedTeamToken($user, teamId: null));

    expect(resolveAuthenticatedTeamFor($user, 'birch-hollow')?->is($hinted))->toBeTrue();
});

it('resolves the team a token is limited to when it is hinted', function () {
    $user    = createTeamContextUser();
    $limited = createTeamContextTeam('amber-grove', $user);
    createTeamContextTeam('birch-hollow', $user);

    $user->withChiefRemoteAccessToken(authenticatedTeamToken($user, teamId: $limited->id));

    expect(resolveAuthenticatedTeamFor($user, 'amber-grove')?->is($limited))->toBeTrue();
});

it('does not resolve a hinted team outside the team a token is limited to', function () {
    $user    = createTeamContextUser();
    $limited = createTeamContextTeam('amber-grove', $user);
    createTeamContextTeam('birch-hollow', $user);

    $user->withChiefRemoteAccessToken(authenticatedTeamToken($user, teamId: $limited->id));

    expect(resolveAuthenticatedTeamFor($user, 'birch-hollow'))->toBeNull();
});

it('does not resolve a hinted team the user does not belong to', function () {
    $user = createTeamContextUser();
    createTeamContextTeam('amber-grove', $user);
    createTeamContextTeam('birch-hollow');

    expect(resolveAuthenticatedTeamFor($user, 'birch-hollow'))->toBeNull()
        ->and(resolveAuthenticatedTeamFor($user, 'unknown-team'))->toBeNull();
});

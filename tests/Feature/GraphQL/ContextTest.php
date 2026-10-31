<?php

use Carbon\Carbon;
use Illuminate\Http\Request;
use ChiefTools\SDK\Entities\Team;
use ChiefTools\SDK\GraphQL\Context;
use ChiefTools\SDK\Auth\ChiefRemoteAccessToken;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function contextTestToken(?int $teamId = 905, ?string $userId = null): ChiefRemoteAccessToken
{
    return new ChiefRemoteAccessToken(
        id: 'synthetic-token',
        name: 'Synthetic token',
        prefix: $userId === null ? 'ctt' : 'ctp',
        scopes: ['synthetic:links:read'],
        userId: $userId,
        teamId: $teamId,
        expiresAt: Carbon::now()->addDay(),
    );
}

function contextTestRequestFor(mixed $authenticatable): Request
{
    $request = Request::create('/api/graphql');
    $request->setUserResolver(static fn () => $authenticatable);

    return $request;
}

it('exposes the token of an authenticated team', function () {
    $team = new Team;
    $team->forceFill(['id' => 905, 'slug' => 'fern-valley', 'name' => 'Fern Valley'])->save();
    $team->withChiefRemoteAccessToken($token = contextTestToken());

    $context = new Context(contextTestRequestFor($team));

    expect($context->user())->toBeNull()
        ->and($context->team()?->is($team))->toBeTrue()
        ->and($context->isAuthenticated())->toBeTrue()
        ->and($context->token())->toBe($token);
});

it('exposes the token of an authenticated user', function () {
    $user = createTeamContextUser();
    createTeamContextTeam('fern-valley', $user);
    $user->withChiefRemoteAccessToken($token = contextTestToken(teamId: null, userId: $user->chief_id));

    $context = new Context(contextTestRequestFor($user));

    expect($context->user())->not->toBeNull()
        ->and($context->isAuthenticated())->toBeTrue()
        ->and($context->token())->toBe($token);
});

it('is not authenticated without a user or team', function () {
    $context = new Context(contextTestRequestFor(null));

    expect($context->isAuthenticated())->toBeFalse()
        ->and($context->token())->toBeNull();
});

<?php

use Tests\TestCase;
use Tests\ScrambleTestCase;
use Illuminate\Http\Request;
use Tests\Fixtures\Models\User;
use ChiefTools\SDK\Entities\Team;
use ChiefTools\SDK\Auth\ChiefRemoteAccessToken;

pest()->extend(TestCase::class)->in('Feature');
pest()->extend(ScrambleTestCase::class)->in('Unit/Scramble');

function createTeamContextUser(bool $isAdmin = false): User
{
    $user = new User;
    $user->forceFill([
        'chief_id' => '5f0e3c3a-9b51-4e0e-8a77-3f2b1d1c6e42',
        'name'     => 'Robin Vale',
        'email'    => 'robin@vale.example',
        'password' => 'synthetic-password',
        'timezone' => 'Europe/Amsterdam',
        'is_admin' => $isAdmin,
    ])->save();

    return $user->refresh();
}

function createTeamContextTeam(string $slug, ?User $member = null): Team
{
    $team = new Team;
    $team->forceFill([
        'slug' => $slug,
        'name' => str($slug)->headline()->toString(),
    ])->save();

    $member?->teams()->attach($team, ['role' => 'member']);

    return $team;
}

function teamContextRequest(?string $teamSlug = null): Request
{
    $request = Request::create('/api/example');

    if ($teamSlug !== null) {
        $request->headers->set('X-Chief-Team', $teamSlug);
    }

    return $request;
}

function teamContextAccessToken(?int $teamId = null): ChiefRemoteAccessToken
{
    return new ChiefRemoteAccessToken(
        id: 'synthetic-token',
        name: 'Synthetic token',
        prefix: 'ctp',
        scopes: [],
        userId: '5f0e3c3a-9b51-4e0e-8a77-3f2b1d1c6e42',
        teamId: $teamId,
        expiresAt: null,
    );
}

/** @param array<string, mixed> $attributes */
function createWebhookEndpoint(Team $team, array $attributes = []): ChiefTools\SDK\Webhook\Outbound\Models\Endpoint
{
    return ChiefTools\SDK\Webhook\Outbound\Models\Endpoint::query()->create([
        'team_id' => $team->getKey(),
        'url'     => 'https://hooks.example.com/chief',
        'events'  => [ChiefTools\SDK\Webhook\Outbound\Models\Endpoint::ALL_EVENTS],
        'secret'  => 'whsec_MfKQ9r8GKYqrTwjUPD8ILPZIo2LaLaSw',
        'status'  => ChiefTools\SDK\Webhook\Outbound\Enums\EndpointStatus::ENABLED,
        ...$attributes,
    ]);
}

/** Resolves every webhook host to the given public addresses, or one of example.com, so tests never hit DNS. */
function resolveWebhookHostsTo(string ...$addresses): void
{
    app()->instance(
        ChiefTools\SDK\Webhook\Outbound\Security\UrlGuard::class,
        new ChiefTools\SDK\Webhook\Outbound\Security\UrlGuard(static fn (string $host): array => $addresses === [] ? ['93.184.215.14'] : $addresses),
    );
}

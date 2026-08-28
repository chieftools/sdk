<?php

use Illuminate\Http\Request;
use ChiefTools\SDK\API\Client;
use ChiefTools\SDK\Entities\Team;
use ChiefTools\SDK\Entities\User;
use Illuminate\Cache\CacheManager;
use ChiefTools\SDK\Socialite\ChiefUser;
use Stayallive\RandomTokens\RandomToken;
use ChiefTools\SDK\Auth\RemoteUserAccessTokenGuard;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('returns 401 authentication state for a cached remote token when the local user has no teams', function () {
    $user  = createRemoteGuardUser();
    $token = RandomToken::generate('ctp', 36);

    cache()->put($token->cacheKey(), remoteGuardValidationResponse($user));

    $request = Request::create('/api/example');
    $request->headers->set('Authorization', 'Bearer ' . $token);

    $guard = new RemoteUserAccessTokenGuard('api', app(Client::class), app(CacheManager::class));

    expect($guard($request))->toBeNull();
});

it('authenticates a cached remote token when the local user has a team', function () {
    $user = createRemoteGuardUser();
    $team = new Team;
    $team->forceFill([
        'id'   => 619,
        'slug' => 'quiet-river',
        'name' => 'Quiet River',
    ])->save();
    $user->teams()->attach($team, ['role' => 'member']);

    $token = RandomToken::generate('ctp', 36);

    cache()->put($token->cacheKey(), remoteGuardValidationResponse($user));

    $request = Request::create('/api/example');
    $request->headers->set('Authorization', 'Bearer ' . $token);

    $guard = new RemoteUserAccessTokenGuard('api', app(Client::class), app(CacheManager::class));

    expect($guard($request)?->is($user))->toBeTrue();
});

it('returns unauthenticated when refreshing a missing token team produces no teams', function () {
    $user = createRemoteGuardUser();
    $team = new Team;
    $team->forceFill([
        'id'   => 733,
        'slug' => 'ember-field',
        'name' => 'Ember Field',
    ])->save();
    $user->teams()->attach($team, ['role' => 'owner']);

    $remote = new ChiefUser([
        'id'              => $user->chief_id,
        'name'            => 'Avery Moss',
        'email'           => 'avery@example.test',
        'timezone'        => 'Europe/Amsterdam',
        'avatar_hash'     => 'remote42',
        'default_team_id' => null,
        'teams'           => [],
    ]);

    $client = $this->mock(Client::class);
    $client->shouldReceive('user')->once()->andReturn($remote);

    $token               = RandomToken::generate('ctp', 36);
    $response            = remoteGuardValidationResponse($user);
    $response['team_id'] = 999;

    cache()->put($token->cacheKey(), $response);

    $request = Request::create('/api/example');
    $request->headers->set('Authorization', 'Bearer ' . $token);

    $guard = new RemoteUserAccessTokenGuard('api', $client, app(CacheManager::class));

    expect($guard($request))->toBeNull()
        ->and($user->fresh()->teams()->doesntExist())->toBeTrue();
});

function createRemoteGuardUser(): User
{
    $user = new User;
    $user->forceFill([
        'chief_id' => '1aca213c-6b8f-4711-a879-7ee5a8b34053',
        'name'     => 'Avery Moss',
        'email'    => 'avery@example.test',
        'password' => 'synthetic-password',
        'timezone' => 'Europe/Amsterdam',
    ])->save();

    return $user;
}

function remoteGuardValidationResponse(User $user): array
{
    return [
        'id'         => 'synthetic-token',
        'name'       => 'Synthetic token',
        'scopes'     => ['domainchief:domains:read'],
        'user_id'    => $user->chief_id,
        'team_id'    => null,
        'team_role'  => null,
        'expires_at' => null,
    ];
}

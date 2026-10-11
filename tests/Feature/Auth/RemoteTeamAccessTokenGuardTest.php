<?php

use Illuminate\Http\Request;
use ChiefTools\SDK\API\Client;
use ChiefTools\SDK\Entities\Team;
use Illuminate\Cache\CacheManager;
use Stayallive\RandomTokens\RandomToken;
use ChiefTools\SDK\Auth\RemoteTeamAccessTokenGuard;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

uses(LazilyRefreshDatabase::class);

it('authenticates a team token when the team header names its team', function () {
    $team    = new Team;
    $team->forceFill(['id' => 905, 'slug' => 'fern-valley', 'name' => 'Fern Valley'])->save();
    $token   = RandomToken::generate('ctt', 36);
    $request = Request::create('/api/example');
    $request->headers->set('Authorization', 'Bearer ' . $token);
    $request->headers->set('X-Chief-Team', 'fern-valley');

    cache()->put($token->cacheKey(), [
        'id'         => 'synthetic-token',
        'name'       => 'Synthetic token',
        'scopes'     => [],
        'team_id'    => 905,
        'expires_at' => null,
    ]);

    $guard = new RemoteTeamAccessTokenGuard('api', app(Client::class), app(CacheManager::class));

    expect($guard($request)?->is($team))->toBeTrue();
});

it('rejects a team token when the team header names another team', function () {
    $team    = new Team;
    $team->forceFill(['id' => 905, 'slug' => 'fern-valley', 'name' => 'Fern Valley'])->save();
    $token   = RandomToken::generate('ctt', 36);
    $request = Request::create('/api/example');
    $request->headers->set('Authorization', 'Bearer ' . $token);
    $request->headers->set('X-Chief-Team', 'glen-harbor');

    cache()->put($token->cacheKey(), [
        'id'         => 'synthetic-token',
        'name'       => 'Synthetic token',
        'scopes'     => [],
        'team_id'    => 905,
        'expires_at' => null,
    ]);

    $guard = new RemoteTeamAccessTokenGuard('api', app(Client::class), app(CacheManager::class));

    $guard($request);
})->throws(NotFoundHttpException::class, 'The team from the X-Chief-Team header does not match the team this token is limited to.');

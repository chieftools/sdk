<?php

use ChiefTools\SDK\Chief;
use Illuminate\Http\Request;
use ChiefTools\SDK\Entities\Team;
use ChiefTools\SDK\Entities\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use ChiefTools\SDK\Webhook\WebhookEvent;
use ChiefTools\SDK\Http\Controllers\Webhook;
use Illuminate\Auth\AuthenticationException;
use ChiefTools\SDK\Webhook\Handlers\TeamUpdated;
use ChiefTools\SDK\Webhook\Handlers\AccountUpdated;
use Tests\Fixtures\Webhook\RegisteredWebhookHandler;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('resolves SDK webhook handlers with enum cases', function () {
    expect(Chief::getWebhookHandler(WebhookEvent::TEAM_UPDATED))->toBe(TeamUpdated::class);
});

it('handles webhooks registered at runtime', function () {
    RegisteredWebhookHandler::$payload = null;

    Chief::registerWebhookHandler(WebhookEvent::CHECKOUT_SESSION_COMPLETED, RegisteredWebhookHandler::class);

    $request = Request::create(
        uri: '/webhooks/chief',
        method: 'POST',
        server: [
            'CONTENT_TYPE' => 'application/json',
        ],
        content: json_encode([
            'event' => enum_value(WebhookEvent::CHECKOUT_SESSION_COMPLETED),
            'data'  => [
                'reference' => 'domain-prepayment-test',
            ],
        ], JSON_THROW_ON_ERROR),
    );

    expect(app(Webhook::class)($request))->toBe([
        'status' => 'registered',
    ])->and(RegisteredWebhookHandler::$payload)->toBe([
        'event' => enum_value(WebhookEvent::CHECKOUT_SESSION_COMPLETED),
        'data'  => [
            'reference' => 'domain-prepayment-test',
        ],
    ]);
});

it('retains the local user and syncs an empty team list from an account update', function () {
    $user = createLocalWebhookUser();
    $team = new Team;
    $team->forceFill([
        'id'   => 312,
        'slug' => 'paper-crane',
        'name' => 'Paper Crane',
    ])->save();
    $user->teams()->attach($team, ['role' => 'owner']);

    app(AccountUpdated::class)([
        'data' => [
            'id'              => $user->chief_id,
            'name'            => 'Morgan Updated',
            'email'           => 'morgan@example.test',
            'timezone'        => 'Europe/Amsterdam',
            'avatar_hash'     => 'changed42',
            'default_team_id' => null,
            'teams'           => [],
        ],
    ]);

    expect($user->fresh()->name)->toBe('Morgan Updated')
        ->and($user->teams()->doesntExist())->toBeTrue()
        ->and($team->fresh())->not->toBeNull();
});

it('requires every local team membership to declare a role', function () {
    $user = createLocalWebhookUser();
    $team = new Team;
    $team->forceFill([
        'id'   => 418,
        'slug' => 'cedar-cove',
        'name' => 'Cedar Cove',
    ])->save();

    expect(fn () => $user->teams()->attach($team))
        ->toThrow(QueryException::class);
});

it('logs out a stateful user whose local team list is empty', function () {
    $user = createLocalWebhookUser();

    expect(fn () => Auth::guard('web')->setUser($user))
        ->toThrow(AuthenticationException::class, 'The user does not have access to applications.');

    expect(Auth::guard('web')->getUser())->toBeNull();
});

it('raises an authentication exception instead of returning no current team', function () {
    $user = createLocalWebhookUser();

    expect(fn () => $user->currentTeam())
        ->toThrow(AuthenticationException::class, 'The user does not have access to applications.');
});

function createLocalWebhookUser(): User
{
    $user = new User;
    $user->forceFill([
        'chief_id' => '1d018668-266f-41b7-8718-3986228c9d3a',
        'name'     => 'Morgan Vale',
        'email'    => 'morgan@example.test',
        'password' => 'synthetic-password',
        'timezone' => 'Europe/Amsterdam',
    ])->save();

    return $user;
}

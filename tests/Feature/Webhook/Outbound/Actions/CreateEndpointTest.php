<?php

use Tests\Fixtures\Webhooks\ExampleEventType;
use Illuminate\Validation\ValidationException;
use ChiefTools\SDK\Webhook\Outbound\Models\Endpoint;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use ChiefTools\SDK\Webhook\Outbound\Enums\EndpointStatus;
use ChiefTools\SDK\Webhook\Outbound\Actions\CreateEndpoint;

uses(LazilyRefreshDatabase::class);

beforeEach(fn () => resolveWebhookHostsTo());

it('creates an enabled endpoint with a new signing secret', function () {
    $team = createTeamContextTeam('lanterns');

    $endpoint = app(CreateEndpoint::class)->handle($team, [
        'url'         => ' https://hooks.example.com/chief ',
        'description' => 'Pager bridge',
        'events'      => [ExampleEventType::LANTERN_LIT->value, ExampleEventType::HARBOR_FLOODED->value],
    ]);

    expect($endpoint->refresh())->team_id->toBe($team->id)
        ->url->toBe('https://hooks.example.com/chief')
        ->description->toBe('Pager bridge')
        ->events->toBe(['lantern.lit', 'harbor.flooded'])
        ->status->toBe(EndpointStatus::ENABLED)
        ->secret->toStartWith('whsec_');
});

it('stores only the wildcard when every event is chosen with others', function () {
    $team = createTeamContextTeam('lanterns');

    $endpoint = app(CreateEndpoint::class)->handle($team, [
        'url'    => 'https://hooks.example.com/chief',
        'events' => [Endpoint::ALL_EVENTS, ExampleEventType::LANTERN_LIT->value],
    ]);

    expect($endpoint->events)->toBe([Endpoint::ALL_EVENTS]);
});

it('rejects invalid endpoints', function (array $input, string $field) {
    $team = createTeamContextTeam('lanterns');

    try {
        app(CreateEndpoint::class)->handle($team, $input);
        $this->fail('The endpoint was created.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey($field);
    }

    expect(Endpoint::query()->count())->toBe(0);
})->with([
    'missing URL'     => [['events' => ['*']], 'url'],
    'plain HTTP'      => [['url' => 'http://hooks.example.com', 'events' => ['*']], 'url'],
    'private address' => [['url' => 'https://10.0.0.1/hooks', 'events' => ['*']], 'url'],
    'no events'       => [['url' => 'https://hooks.example.com', 'events' => []], 'events'],
    'unknown event'   => [['url' => 'https://hooks.example.com', 'events' => ['lantern.stolen']], 'events.0'],
]);

it('rejects endpoints beyond the plan limit', function () {
    $team = createTeamContextTeam('lanterns');
    $team->forceFill(['limits' => ['webhook_endpoints' => 1]])->save();
    createWebhookEndpoint($team);

    app(CreateEndpoint::class)->handle($team, ['url' => 'https://hooks.example.com/second', 'events' => ['*']]);
})->throws(ValidationException::class, 'Your plan includes up to 1 webhook endpoint');

it('allows any number of endpoints when the plan sets no limit', function () {
    $team = createTeamContextTeam('lanterns');
    createWebhookEndpoint($team);
    createWebhookEndpoint($team);

    app(CreateEndpoint::class)->handle($team, ['url' => 'https://hooks.example.com/third', 'events' => ['*']]);

    expect(Endpoint::query()->count())->toBe(3);
});

<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use ChiefTools\SDK\Webhook\Outbound\Webhooks;
use Tests\Fixtures\Webhooks\ExampleEventType;
use ChiefTools\SDK\Auth\ChiefRemoteAccessToken;
use ChiefTools\SDK\Webhook\Outbound\Models\Delivery;
use ChiefTools\SDK\Webhook\Outbound\Models\Endpoint;
use ChiefTools\SDK\Webhook\Outbound\Jobs\SendDelivery;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use ChiefTools\SDK\Webhook\Outbound\Enums\DeliveryStatus;
use ChiefTools\SDK\Webhook\Outbound\Enums\EndpointStatus;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    resolveWebhookHostsTo();
    Queue::fake([SendDelivery::class]);

    Route::prefix('api/v1')->as('api.v1.')->middleware('api')->group(static fn () => Route::chiefWebhooksApi('lanternchief:webhooks'));
    app('router')->getRoutes()->refreshNameLookups();

    $this->user = createTeamContextUser();
    $this->team = createTeamContextTeam('lanterns', $this->user);
    $this->actingAs($this->user);
});

/** A team token of the team with the scopes, the token type integrations use. */
function actingAsWebhooksTeamToken(ChiefTools\SDK\Entities\Team $team, array $scopes): void
{
    test()->actingAs($team->withChiefRemoteAccessToken(new ChiefRemoteAccessToken(
        id: 'synthetic-team-token',
        name: 'Synthetic team token',
        prefix: 'ctt',
        scopes: $scopes,
        userId: null,
        teamId: $team->getKey(),
        expiresAt: null,
    )));
}

it('lists the event types endpoints can subscribe to', function () {
    $this->getJson('/api/v1/webhooks/event-types')
        ->assertOk()
        ->assertJsonPath('data.0', ['type' => 'lantern.lit', 'group' => 'Lanterns', 'description' => 'A lantern was lit.'])
        ->assertJsonCount(count(ExampleEventType::cases()), 'data');
});

it('creates an endpoint and returns its secret only then', function () {
    $response = $this->postJson('/api/v1/webhooks/endpoints', [
        'url'         => 'https://hooks.example.com/chief',
        'description' => 'Paging',
        'events'      => ['lantern.lit', 'harbor.flooded'],
    ])->assertCreated();

    $endpoint = Endpoint::query()->sole();
    $response->assertJsonPath('data.id', $endpoint->formatted_id)
        ->assertJsonPath('data.events', ['lantern.lit', 'harbor.flooded'])
        ->assertJsonPath('data.status', 'enabled')
        ->assertJsonPath('data.secret', $endpoint->secret);

    $this->getJson("/api/v1/webhooks/endpoints/{$endpoint->formatted_id}")->assertOk()->assertJsonPath('data.secret', null);
    $this->getJson('/api/v1/webhooks/endpoints')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.secret', null);
});

it('refuses an endpoint the plan has no room for, or a private URL', function () {
    $this->team->forceFill(['limits' => ['webhook_endpoints' => 0]])->save();

    $this->postJson('/api/v1/webhooks/endpoints', ['url' => 'https://hooks.example.com/chief', 'events' => ['*']])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['url' => 'Webhooks are not included in your plan.']);

    $this->team->forceFill(['limits' => []])->save();
    resolveWebhookHostsTo('10.0.0.8');

    $this->postJson('/api/v1/webhooks/endpoints', ['url' => 'https://hooks.example.com/chief', 'events' => ['*']])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('url');

    expect(Endpoint::query()->count())->toBe(0);
});

it('updates only the fields that are sent', function () {
    $endpoint = createWebhookEndpoint($this->team, ['description' => 'Paging', 'events' => ['lantern.lit']]);

    $this->patchJson("/api/v1/webhooks/endpoints/{$endpoint->formatted_id}", ['events' => ['*']])
        ->assertOk()
        ->assertJsonPath('data.events', ['*'])
        ->assertJsonPath('data.description', 'Paging')
        ->assertJsonPath('data.url', 'https://hooks.example.com/chief');
});

it('does not find the webhooks of another team', function (string $method, string $path) {
    $other    = createTeamContextTeam('harbors');
    $endpoint = createWebhookEndpoint($other);
    $event    = app(Webhooks::class)->sendTo($endpoint, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1']);

    $path = strtr($path, ['{endpoint}' => $endpoint->formatted_id, '{delivery}' => $event->formatted_id, '{event}' => $event->event->formatted_id]);

    $this->json($method, $path, ['endpoint' => $endpoint->formatted_id])->assertNotFound();

    expect($endpoint->fresh()->status)->toBe(EndpointStatus::ENABLED);
})->with([
    'get endpoint'     => ['GET', '/api/v1/webhooks/endpoints/{endpoint}'],
    'delete endpoint'  => ['DELETE', '/api/v1/webhooks/endpoints/{endpoint}'],
    'disable endpoint' => ['POST', '/api/v1/webhooks/endpoints/{endpoint}/disable'],
    'deliveries'       => ['GET', '/api/v1/webhooks/endpoints/{endpoint}/deliveries'],
    'get delivery'     => ['GET', '/api/v1/webhooks/deliveries/{delivery}'],
    'resend delivery'  => ['POST', '/api/v1/webhooks/deliveries/{delivery}/resend'],
    'get event'        => ['GET', '/api/v1/webhooks/events/{event}'],
    'resend event'     => ['POST', '/api/v1/webhooks/events/{event}/resend'],
]);

it('rotates the secret and refuses a second rotation while the previous secret still signs', function () {
    $endpoint = createWebhookEndpoint($this->team);

    $this->postJson("/api/v1/webhooks/endpoints/{$endpoint->formatted_id}/rotate-secret")
        ->assertOk()
        ->assertJsonPath('data.secret', $endpoint->fresh()->secret)
        ->assertJsonPath('data.previous_secret_expires_at', $endpoint->fresh()->previous_secret_expires_at->toIso8601String());

    $this->postJson("/api/v1/webhooks/endpoints/{$endpoint->formatted_id}/rotate-secret")->assertUnprocessable();

    $before = $endpoint->fresh()->secret;

    $this->postJson("/api/v1/webhooks/endpoints/{$endpoint->formatted_id}/rotate-secret", ['replace_previous' => true])
        ->assertOk()
        ->assertJsonPath('data.secret', fn (string $secret): bool => $secret !== $before);
});

it('disables, enables and deletes an endpoint', function () {
    $endpoint = createWebhookEndpoint($this->team);

    $this->postJson("/api/v1/webhooks/endpoints/{$endpoint->formatted_id}/disable")
        ->assertOk()
        ->assertJsonPath('data.status', 'disabled')
        ->assertJsonPath('data.disabled_reason', 'manual');
    $this->postJson("/api/v1/webhooks/endpoints/{$endpoint->formatted_id}/enable")
        ->assertOk()
        ->assertJsonPath('data.status', 'enabled')
        ->assertJsonPath('data.disabled_reason', null);
    $this->deleteJson("/api/v1/webhooks/endpoints/{$endpoint->formatted_id}")->assertNoContent();

    expect(Endpoint::query()->count())->toBe(0);
});

it('sends a test event and shows the delivery with its attempts', function () {
    $endpoint = createWebhookEndpoint($this->team);

    $response = $this->postJson("/api/v1/webhooks/endpoints/{$endpoint->formatted_id}/test")
        ->assertAccepted()
        ->assertJsonPath('data.endpoint_id', $endpoint->formatted_id)
        ->assertJsonPath('data.event_type', 'webhook_endpoint.test')
        ->assertJsonPath('data.status', 'pending');
    Queue::assertPushed(SendDelivery::class);

    $delivery = Delivery::query()->sole();
    $delivery->attemptLog()->create(['endpoint_id' => $endpoint->id, 'number' => 1, 'succeeded' => false, 'request_headers' => ['webhook-id' => 'event_1'], 'response_status' => 500, 'response_headers' => ['content-type' => 'text/plain'], 'response_body' => 'Oops', 'duration_ms' => 120, 'attempted_at' => now()]);

    $this->getJson("/api/v1/webhooks/deliveries/{$response->json('data.id')}")
        ->assertOk()
        ->assertJsonPath('data.attempt_log.0.number', 1)
        ->assertJsonPath('data.attempt_log.0.request.headers', ['webhook-id' => 'event_1'])
        ->assertJsonPath('data.attempt_log.0.response', ['status' => 500, 'headers' => ['content-type' => 'text/plain'], 'body' => 'Oops', 'truncated' => false]);
});

it('lists an endpoint\'s deliveries by status and type, and resends one', function () {
    $endpoint = createWebhookEndpoint($this->team);
    $webhooks = app(Webhooks::class);
    $failed   = $webhooks->sendTo($endpoint, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1']);
    $failed->forceFill(['status' => DeliveryStatus::FAILED, 'attempts' => 9])->save();
    $webhooks->sendTo($endpoint, ExampleEventType::HARBOR_FLOODED, ['id' => 'har_1']);

    $this->getJson("/api/v1/webhooks/endpoints/{$endpoint->formatted_id}/deliveries?status=failed")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $failed->formatted_id);
    $this->getJson("/api/v1/webhooks/endpoints/{$endpoint->formatted_id}/deliveries?type=harbor.flooded")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.event_type', 'harbor.flooded');

    $this->postJson("/api/v1/webhooks/deliveries/{$failed->formatted_id}/resend")
        ->assertAccepted()
        ->assertJsonPath('data.id', $failed->formatted_id);

    expect($failed->fresh()->manual_attempt_at)->not->toBeNull();
});

it('lists events with their payload and resends one to another endpoint', function () {
    $receiver = createWebhookEndpoint($this->team);
    $missed   = createWebhookEndpoint($this->team, ['url' => 'https://hooks.example.com/missed']);
    $event    = app(Webhooks::class)->sendTo($receiver, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1'])->event;

    $this->getJson('/api/v1/webhooks/events?type=lantern.lit')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.payload.data.object', ['id' => 'lan_1'])
        ->assertJsonMissingPath('data.0.deliveries');
    $this->getJson('/api/v1/webhooks/events?type=harbor.flooded')->assertOk()->assertJsonCount(0, 'data');

    $this->postJson("/api/v1/webhooks/events/{$event->formatted_id}/resend", ['endpoint' => $missed->formatted_id])
        ->assertAccepted()
        ->assertJsonPath('data.endpoint_id', $missed->formatted_id);

    $this->getJson("/api/v1/webhooks/events/{$event->formatted_id}")
        ->assertOk()
        ->assertJsonCount(2, 'data.deliveries');
});

it('lets a team token read with the read scope and change with the write scope', function () {
    $endpoint = createWebhookEndpoint($this->team);
    createWebhookEndpoint(createTeamContextTeam('harbors'));

    actingAsWebhooksTeamToken($this->team, ['lanternchief:webhooks:read']);

    $this->getJson('/api/v1/webhooks/endpoints')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $endpoint->formatted_id);
    $this->postJson("/api/v1/webhooks/endpoints/{$endpoint->formatted_id}/disable")->assertForbidden();

    actingAsWebhooksTeamToken($this->team, ['lanternchief:webhooks:write']);

    $this->postJson("/api/v1/webhooks/endpoints/{$endpoint->formatted_id}/disable")->assertOk();
});

it('asks the app whether a person may manage the team\'s webhooks', function () {
    Gate::define('manageWebhooks', static fn (): bool => false);

    $this->getJson('/api/v1/webhooks/endpoints')->assertForbidden();
});

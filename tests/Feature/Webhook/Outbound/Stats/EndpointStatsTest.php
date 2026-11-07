<?php

use Illuminate\Support\Facades\Queue;
use ChiefTools\SDK\Webhook\Outbound\Webhooks;
use Tests\Fixtures\Webhooks\ExampleEventType;
use ChiefTools\SDK\Webhook\Outbound\Models\Attempt;
use ChiefTools\SDK\Webhook\Outbound\Models\Delivery;
use ChiefTools\SDK\Webhook\Outbound\Jobs\SendDelivery;
use ChiefTools\SDK\Webhook\Outbound\Stats\EndpointStats;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/** Records an attempt of the endpoint's only delivery. */
function recordAttempt(Delivery $delivery, string $at, bool $succeeded, ?int $durationMs): void
{
    Attempt::query()->create([
        'delivery_id'     => $delivery->id,
        'endpoint_id'     => $delivery->endpoint_id,
        'number'          => 1,
        'succeeded'       => $succeeded,
        'request_headers' => [],
        'duration_ms'     => $durationMs,
        'attempted_at'    => $at,
    ]);
}

it('counts attempts and response times per hour over the last day', function () {
    Queue::fake([SendDelivery::class]);
    $endpoint = createWebhookEndpoint(createTeamContextTeam('lanterns'));
    app(Webhooks::class)->dispatch($endpoint->team_id, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1']);
    $delivery = Delivery::query()->sole();
    recordAttempt($delivery, '2026-11-07 11:05:00', true, 100);
    recordAttempt($delivery, '2026-11-07 11:55:00', false, 300);
    recordAttempt($delivery, '2026-11-07 11:56:00', false, null);
    recordAttempt($delivery, '2026-11-06 11:59:00', true, 50);

    $series = app(EndpointStats::class)->series($endpoint, '24h', Carbon\CarbonImmutable::parse('2026-11-07 11:30:00'));

    expect($series)->toHaveCount(24)
        ->and($series[0]['date']->format('Y-m-d H:i'))->toBe('2026-11-06 12:00')
        ->and($series[22]['attempts'])->toBe(0)
        ->and($series[23])->toMatchArray([
            'attempts'  => 3,
            'succeeded' => 1,
            'failed'    => 2,
            'avg_ms'    => 200,
            'max_ms'    => 300,
        ]);
});

it('summarizes the success rate of a range', function () {
    Queue::fake([SendDelivery::class]);
    $endpoint = createWebhookEndpoint(createTeamContextTeam('lanterns'));
    app(Webhooks::class)->dispatch($endpoint->team_id, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1']);
    $delivery = Delivery::query()->sole();
    recordAttempt($delivery, '2026-11-03 09:00:00', true, 100);
    recordAttempt($delivery, '2026-11-05 09:00:00', true, 100);
    recordAttempt($delivery, '2026-11-07 09:00:00', false, 400);

    $summary = app(EndpointStats::class)->summary($endpoint, '7d', Carbon\CarbonImmutable::parse('2026-11-07 11:30:00'));

    expect($summary)->toBe([
        'attempts'     => 3,
        'succeeded'    => 2,
        'failed'       => 1,
        'success_rate' => 66.7,
        'avg_ms'       => 200,
    ]);
});

it('sums the attempts of the last day per endpoint', function () {
    Queue::fake([SendDelivery::class]);
    $endpoint = createWebhookEndpoint(createTeamContextTeam('lanterns'));
    $quiet    = createWebhookEndpoint($endpoint->team);
    app(Webhooks::class)->dispatch($endpoint->team_id, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1']);
    $delivery = Delivery::query()->where('endpoint_id', $endpoint->id)->sole();
    recordAttempt($delivery, '2026-11-07 10:00:00', true, 100);
    recordAttempt($delivery, '2026-11-07 11:00:00', true, 100);
    recordAttempt($delivery, '2026-11-07 11:10:00', false, 100);
    recordAttempt($delivery, '2026-11-06 11:00:00', false, 100);

    $stats = app(EndpointStats::class)->lastDay([$endpoint, $quiet], Carbon\CarbonImmutable::parse('2026-11-07 11:30:00'));

    expect($stats)->toHaveKeys([$endpoint->id])->not->toHaveKey($quiet->id)
        ->and($stats[$endpoint->id])->toMatchArray(['attempts' => 3, 'succeeded' => 2, 'success_rate' => 66.7])
        ->and($stats[$endpoint->id]['hours'])->toHaveCount(24)
        ->and($stats[$endpoint->id]['hours'][0]['hour']->format('Y-m-d H:i'))->toBe('2026-11-06 12:00')
        ->and(array_map(static fn (array $hour): array => [$hour['succeeded'], $hour['failed']], array_slice($stats[$endpoint->id]['hours'], -2)))->toBe([[1, 0], [1, 1]]);
});

it('averages only the response times that were measured', function () {
    Queue::fake([SendDelivery::class]);
    $endpoint = createWebhookEndpoint(createTeamContextTeam('lanterns'));
    app(Webhooks::class)->dispatch($endpoint->team_id, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1']);
    $delivery = Delivery::query()->sole();
    recordAttempt($delivery, '2026-11-07 09:00:00', true, 1000);
    recordAttempt($delivery, '2026-11-07 10:00:00', false, null);
    recordAttempt($delivery, '2026-11-07 10:01:00', false, null);

    $summary = app(EndpointStats::class)->summary($endpoint, '24h', Carbon\CarbonImmutable::parse('2026-11-07 11:30:00'));

    expect($summary)->attempts->toBe(3)->avg_ms->toBe(1000);
});

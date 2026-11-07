<?php

use Illuminate\Support\Facades\Queue;
use ChiefTools\SDK\Webhook\Outbound\Webhooks;
use Tests\Fixtures\Webhooks\ExampleEventType;
use ChiefTools\SDK\Webhook\Outbound\Models\Delivery;
use ChiefTools\SDK\Webhook\Outbound\Jobs\SendDelivery;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use ChiefTools\SDK\Webhook\Outbound\Enums\DeliveryStatus;
use ChiefTools\SDK\Webhook\Outbound\Console\DispatchDueDeliveries;

uses(LazilyRefreshDatabase::class);

it('queues due attempts once and leaves the others alone', function () {
    $this->travelTo(Carbon\CarbonImmutable::parse('2026-11-07 12:00:00'));
    Queue::fake([SendDelivery::class]);
    $endpoint = createWebhookEndpoint(createTeamContextTeam('lanterns'));
    foreach (range(1, 4) as $number) {
        app(Webhooks::class)->dispatch($endpoint->team_id, ExampleEventType::LANTERN_LIT, ['id' => "lan_{$number}"]);
    }
    [$due, $later, $failed, $leased] = Delivery::query()->orderBy('id')->get()->all();
    $due->forceFill(['attempts' => 2, 'next_attempt_at' => now()->subMinute()])->save();
    $later->forceFill(['attempts' => 1, 'next_attempt_at' => now()->addMinute()])->save();
    $failed->forceFill(['status' => DeliveryStatus::FAILED, 'next_attempt_at' => now()->subMinute()])->save();
    Queue::fake([SendDelivery::class]);

    $this->artisan('chief:webhooks:dispatch-due')->assertSuccessful();
    $this->artisan('chief:webhooks:dispatch-due')->assertSuccessful();

    Queue::assertPushed(SendDelivery::class, 1);
    Queue::assertPushed(SendDelivery::class, fn (SendDelivery $job) => $job->deliveryId === $due->id && $job->attempts === 2);
    expect($due->refresh()->next_attempt_at->format('Y-m-d H:i:s'))->toBe('2026-11-07 12:10:00');
});

it('hands the claim back and stops when the queue fails', function () {
    $this->travelTo(Carbon\CarbonImmutable::parse('2026-11-07 12:00:00'));
    Queue::fake([SendDelivery::class]);
    $endpoint = createWebhookEndpoint(createTeamContextTeam('lanterns'));
    app(Webhooks::class)->dispatch($endpoint->team_id, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1']);
    $delivery = Delivery::query()->sole();
    $delivery->forceFill(['next_attempt_at' => now()->subMinute()])->save();
    Illuminate\Support\Facades\Exceptions::fake();
    Illuminate\Support\Facades\Bus::shouldReceive('dispatch')->once()->andThrow(new RuntimeException('The queue is down.'));

    $this->artisan('chief:webhooks:dispatch-due')->assertFailed();

    expect($delivery->refresh()->next_attempt_at->format('Y-m-d H:i:s'))->toBe('2026-11-07 11:59:00');
    Illuminate\Support\Facades\Exceptions::assertReported(RuntimeException::class);
});

it('queues at most its share of due deliveries per endpoint each run', function () {
    $this->travelTo(Carbon\CarbonImmutable::parse('2026-11-07 12:00:00'));
    Queue::fake([SendDelivery::class]);
    $team = createTeamContextTeam('lanterns');
    $busy = createWebhookEndpoint($team, ['events' => [ExampleEventType::LANTERN_LIT->value]]);
    $calm = createWebhookEndpoint($team, ['events' => [ExampleEventType::HARBOR_FLOODED->value]]);
    foreach (range(1, DispatchDueDeliveries::MAX_PER_ENDPOINT + 2) as $number) {
        app(Webhooks::class)->dispatch($team, ExampleEventType::LANTERN_LIT, ['id' => "lan_{$number}"]);
    }
    app(Webhooks::class)->dispatch($team, ExampleEventType::HARBOR_FLOODED, ['id' => 'har_1']);
    Delivery::query()->update(['next_attempt_at' => now()->subMinute()]);
    Queue::fake([SendDelivery::class]);

    $this->artisan('chief:webhooks:dispatch-due')->assertSuccessful();

    Queue::assertPushed(SendDelivery::class, DispatchDueDeliveries::MAX_PER_ENDPOINT + 1);
    expect(Delivery::query()->where('endpoint_id', $busy->id)->where('next_attempt_at', '<=', now())->count())->toBe(2)
        ->and(Delivery::query()->where('endpoint_id', $calm->id)->where('next_attempt_at', '<=', now())->count())->toBe(0);
});

<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use ChiefTools\SDK\Webhook\Outbound\Webhooks;
use Tests\Fixtures\Webhooks\ExampleEventType;
use ChiefTools\SDK\Webhook\Outbound\Models\Delivery;
use ChiefTools\SDK\Webhook\Outbound\Jobs\SendDelivery;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use ChiefTools\SDK\Webhook\Outbound\Enums\DeliveryStatus;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Http::preventStrayRequests();
    resolveWebhookHostsTo();
});

it('makes the attempt it was queued for', function () {
    Queue::fake([SendDelivery::class]);
    $endpoint = createWebhookEndpoint(createTeamContextTeam('lanterns'));
    app(Webhooks::class)->dispatch($endpoint->team_id, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1']);
    $delivery = Delivery::query()->sole();
    Http::fake(['hooks.example.com/chief' => Http::response()]);

    app()->call([new SendDelivery($delivery->id, 0), 'handle']);

    expect($delivery->refresh()->status)->toBe(DeliveryStatus::SUCCEEDED);
});

it('does nothing when the delivery moved on since it was queued', function () {
    Queue::fake([SendDelivery::class]);
    $endpoint = createWebhookEndpoint(createTeamContextTeam('lanterns'));
    app(Webhooks::class)->dispatch($endpoint->team_id, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1']);
    $delivery = Delivery::query()->sole();
    $delivery->forceFill(['attempts' => 1])->save();
    Http::fake(['hooks.example.com/chief' => Http::response()]);

    app()->call([new SendDelivery($delivery->id, 0), 'handle']);

    Http::assertNothingSent();
    expect($delivery->refresh()->attempts)->toBe(1);
});

it('runs on the configured queue', function () {
    config(['chief.webhooks.queue' => 'webhooks']);

    expect((new SendDelivery('01jcexample', 0))->queue)->toBe('webhooks');
});

it('makes a manual attempt of a delivery that already finished', function () {
    Queue::fake([SendDelivery::class]);
    $endpoint = createWebhookEndpoint(createTeamContextTeam('lanterns'));
    app(Webhooks::class)->dispatch($endpoint->team_id, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1']);
    $delivery = Delivery::query()->sole();
    $delivery->forceFill(['status' => DeliveryStatus::FAILED, 'attempts' => 9, 'next_attempt_at' => null])->save();
    Http::fake(['hooks.example.com/chief' => Http::response()]);

    app()->call([new SendDelivery($delivery->id, 9), 'handle']);
    app()->call([new SendDelivery($delivery->id, 9, manual: true), 'handle']);

    Http::assertSentCount(1);
    expect($delivery->refresh())->status->toBe(DeliveryStatus::SUCCEEDED)
        ->attempts->toBe(10);
});

it('comes back later without an attempt while its endpoint has no room', function () {
    Queue::fake([SendDelivery::class]);
    $endpoint = createWebhookEndpoint(createTeamContextTeam('lanterns'));
    app(Webhooks::class)->dispatch($endpoint->team_id, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1']);
    $delivery = Delivery::query()->sole();
    $this->mock(ChiefTools\SDK\Webhook\Outbound\EndpointPacing::class)->shouldReceive('attempt')->once()->andReturn(3);
    Http::fake(['hooks.example.com/chief' => Http::response()]);

    app()->call([new SendDelivery($delivery->id, 0, waits: 4), 'handle']);

    Http::assertNothingSent();
    expect($delivery->refresh())->attempts->toBe(0)->status->toBe(DeliveryStatus::PENDING);
    Queue::assertPushed(SendDelivery::class, fn (SendDelivery $job) => $job->deliveryId === $delivery->id && $job->waits === 5 && $job->attempts === 0 && $job->delay >= 11 && $job->delay <= 13);
});

it('hands a delivery that keeps waiting to the sweeper', function () {
    $this->travelTo(Carbon\CarbonImmutable::parse('2026-11-07 12:00:00'));
    Queue::fake([SendDelivery::class]);
    $endpoint = createWebhookEndpoint(createTeamContextTeam('lanterns'));
    app(Webhooks::class)->dispatch($endpoint->team_id, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1']);
    $delivery = Delivery::query()->sole();
    Queue::fake([SendDelivery::class]);
    $this->mock(ChiefTools\SDK\Webhook\Outbound\EndpointPacing::class)->shouldReceive('attempt')->once()->andReturn(1);

    app()->call([new SendDelivery($delivery->id, 0, waits: SendDelivery::MAX_WAITS), 'handle']);

    Queue::assertNotPushed(SendDelivery::class);
    expect($delivery->refresh()->next_attempt_at->format('H:i:s'))->toBe('12:00:00');
});

describe('resends on a queue that runs jobs right away', function () {
    /** A delivery that already failed for good, ready to be resent by hand. */
    function failedDelivery(): Delivery
    {
        $queue    = Queue::getFacadeRoot();
        Queue::fake([SendDelivery::class]);
        $endpoint = createWebhookEndpoint(createTeamContextTeam('lanterns'));
        app(Webhooks::class)->dispatch($endpoint->team_id, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1']);
        $delivery = Delivery::query()->sole();
        $delivery->forceFill(['status' => DeliveryStatus::FAILED, 'attempts' => 9, 'next_attempt_at' => null])->save();
        // The sync queue again, which runs a job the moment it is queued
        Queue::swap($queue);

        return $delivery;
    }

    it('sends the resend once its endpoint has room', function () {
        $delivery = failedDelivery();
        $tries    = 0;
        $this->mock(ChiefTools\SDK\Webhook\Outbound\EndpointPacing::class)
            ->shouldReceive('attempt')
            ->andReturnUsing(static function (string $endpointId, Closure $send) use (&$tries): ?int {
                if ($tries++ === 0) {
                    return 1;
                }

                $send();

                return null;
            });
        Http::fake(['hooks.example.com/chief' => Http::response()]);

        app(ChiefTools\SDK\Webhook\Outbound\Actions\ResendDelivery::class)->handle($delivery);

        expect($delivery->refresh())->status->toBe(DeliveryStatus::SUCCEEDED)
            ->attempts->toBe(10);
    });

    it('leaves a resend that never gets room to the sweeper', function () {
        $this->travelTo(Carbon\CarbonImmutable::parse('2026-11-07 12:00:00'));
        $delivery = failedDelivery();
        $this->mock(ChiefTools\SDK\Webhook\Outbound\EndpointPacing::class)->shouldReceive('attempt')->andReturn(1);
        Http::fake(['hooks.example.com/chief' => Http::response()]);

        app(ChiefTools\SDK\Webhook\Outbound\Actions\ResendDelivery::class)->handle($delivery);

        Http::assertNothingSent();
        expect($delivery->refresh())->status->toBe(DeliveryStatus::FAILED)
            ->attempts->toBe(9)
            ->next_attempt_at->toBeNull()
            ->manual_attempt_at->format('H:i:s')->toBe('12:00:00');
    });

    it('sends a resend the sweeper took over as a resend', function () {
        $delivery = failedDelivery();
        $delivery->forceFill(['status' => DeliveryStatus::SUCCEEDED, 'attempts' => 1, 'manual_attempt_at' => now()->subMinute()])->save();
        Http::fake(['hooks.example.com/chief' => Http::response('Nope', 500)]);

        $this->artisan('chief:webhooks:dispatch-due')->assertSuccessful();

        // One more attempt that failed, not the start of automatic retries
        expect($delivery->refresh())->status->toBe(DeliveryStatus::FAILED)
            ->attempts->toBe(2)
            ->next_attempt_at->toBeNull()
            ->manual_attempt_at->toBeNull()
            ->and($delivery->attemptLog()->where('number', 2)->sole()->manual)->toBeTrue();
    });

    it('keeps the scheduled retry of a delivery whose resend has to wait', function () {
        $this->travelTo(Carbon\CarbonImmutable::parse('2026-11-07 12:00:00'));
        $delivery = failedDelivery();
        $delivery->forceFill(['status' => DeliveryStatus::PENDING, 'attempts' => 5, 'next_attempt_at' => '2026-11-07 18:00:00'])->save();
        $this->mock(ChiefTools\SDK\Webhook\Outbound\EndpointPacing::class)->shouldReceive('attempt')->andReturn(1);

        app(ChiefTools\SDK\Webhook\Outbound\Actions\ResendDelivery::class)->handle($delivery);

        expect($delivery->refresh())->next_attempt_at->format('H:i')->toBe('18:00')
            ->manual_attempt_at->format('H:i')->toBe('12:00');
    });
});

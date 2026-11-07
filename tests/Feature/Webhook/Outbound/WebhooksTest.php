<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use ChiefTools\SDK\Webhook\Outbound\Webhooks;
use Tests\Fixtures\Webhooks\ExampleEventType;
use ChiefTools\SDK\Webhook\Outbound\Models\Event;
use ChiefTools\SDK\Webhook\Outbound\Models\Delivery;
use ChiefTools\SDK\Webhook\Outbound\Models\Endpoint;
use ChiefTools\SDK\Webhook\Outbound\Jobs\SendDelivery;
use ChiefTools\SDK\Webhook\Outbound\Enums\BuiltInEvent;
use ChiefTools\SDK\Webhook\Outbound\Enums\RecordResult;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use ChiefTools\SDK\Webhook\Outbound\Enums\DeliveryStatus;
use ChiefTools\SDK\Webhook\Outbound\Enums\EndpointStatus;

uses(LazilyRefreshDatabase::class);

it('stores the event once and queues a delivery for every subscribed endpoint', function () {
    $this->travelTo(Carbon\CarbonImmutable::parse('2026-11-07 12:30:15.250'));
    Queue::fake([SendDelivery::class]);
    $team       = createTeamContextTeam('lanterns');
    $everything = createWebhookEndpoint($team);
    $lanterns   = createWebhookEndpoint($team, ['events' => [ExampleEventType::LANTERN_LIT->value]]);
    createWebhookEndpoint($team, ['events' => [ExampleEventType::HARBOR_FLOODED->value]]);

    app(Webhooks::class)->dispatch($team, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1', 'brightness' => 80], ['reason' => 'dusk']);

    $event = Event::query()->sole();
    expect(json_decode($event->payload, true))->toBe([
        'id'          => 'event_' . $event->id,
        'type'        => 'lantern.lit',
        'timestamp'   => '2026-11-07T12:30:15.250Z',
        'api_version' => 'v1',
        'data'        => [
            'object' => ['id' => 'lan_1', 'brightness' => 80],
            'reason' => 'dusk',
        ],
    ]);
    expect(Delivery::query()->pluck('endpoint_id')->sort()->values()->all())->toBe(collect([$everything->id, $lanterns->id])->sort()->values()->all());
    expect(Delivery::query()->get())->each(fn ($delivery) => $delivery->status->toBe(DeliveryStatus::PENDING)
        ->next_attempt_at->format('Y-m-d H:i:s')->toBe('2026-11-07 12:40:15'));
    Queue::assertPushed(SendDelivery::class, 2);
});

it('records nothing when no enabled endpoint subscribed to the event', function () {
    Queue::fake([SendDelivery::class]);
    $team = createTeamContextTeam('harbors');
    createWebhookEndpoint($team, ['events' => [ExampleEventType::HARBOR_FLOODED->value]]);
    createWebhookEndpoint($team, ['status' => EndpointStatus::DISABLED]);
    createWebhookEndpoint(createTeamContextTeam('elsewhere'));

    app(Webhooks::class)->dispatch($team, ExampleEventType::LANTERN_DIMMED, ['id' => 'lan_1']);

    expect(Event::query()->count())->toBe(0);
    Queue::assertNotPushed(SendDelivery::class);
});

it('sends to an endpoint added after the subscriptions were cached', function () {
    Queue::fake([SendDelivery::class]);
    $team     = createTeamContextTeam('lanterns');
    app(Webhooks::class)->dispatch($team, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1']);
    $endpoint = createWebhookEndpoint($team);

    app(Webhooks::class)->dispatch($team, ExampleEventType::LANTERN_LIT, ['id' => 'lan_2']);

    expect(Delivery::query()->sole()->endpoint_id)->toBe($endpoint->id);
});

it('does not send events of a transaction that rolled back', function () {
    Queue::fake([SendDelivery::class]);
    $team = createTeamContextTeam('lanterns');
    createWebhookEndpoint($team);

    rescue(static function () use ($team) {
        DB::transaction(static function () use ($team) {
            app(Webhooks::class)->dispatch($team, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1']);

            throw new RuntimeException('The lantern broke.');
        });
    }, report: false);

    expect(Event::query()->count())->toBe(0);
    Queue::assertNotPushed(SendDelivery::class);
});

it('does not send built-in events to subscriptions', function () {
    Queue::fake([SendDelivery::class]);
    $team = createTeamContextTeam('lanterns');
    createWebhookEndpoint($team);

    app(Webhooks::class)->dispatch($team, BuiltInEvent::TEST, ['id' => 'webhook_1']);

    expect(Event::query()->count())->toBe(0);
});

it('sends nothing while webhooks are disabled for the app', function () {
    Queue::fake([SendDelivery::class]);
    config(['chief.webhooks.enabled' => false]);
    $team = createTeamContextTeam('lanterns');
    createWebhookEndpoint($team);

    app(Webhooks::class)->dispatch($team, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1']);

    expect(Event::query()->count())->toBe(0);
});

it('groups the app events by their heading', function () {
    expect(app(Webhooks::class)->groupedEventTypes())->toBe([
        'Lanterns' => [ExampleEventType::LANTERN_LIT, ExampleEventType::LANTERN_DIMMED],
        'Harbors'  => [ExampleEventType::HARBOR_FLOODED],
    ]);
});

it('encodes the object when dispatched, before the transaction commits', function () {
    Queue::fake([SendDelivery::class]);
    $team    = createTeamContextTeam('lanterns');
    createWebhookEndpoint($team);
    $lantern = new ArrayObject(['id' => 'lan_1', 'state' => 'lit', 'labels' => new stdClass]);
    $object  = new class($lantern) implements JsonSerializable
    {
        public function __construct(
            private ArrayObject $lantern,
        ) {}

        public function jsonSerialize(): array
        {
            return $this->lantern->getArrayCopy();
        }
    };

    DB::transaction(static function () use ($team, $object, $lantern) {
        app(Webhooks::class)->dispatch($team, ExampleEventType::LANTERN_LIT, $object);

        $lantern['state'] = 'dimmed';
    });

    expect(Event::query()->sole()->payload)->toContain('"object":{"id":"lan_1","state":"lit","labels":{}}');
});

it('tells whether an enabled endpoint of the team receives an event', function () {
    $team = createTeamContextTeam('lanterns');
    createWebhookEndpoint($team, ['events' => [ExampleEventType::LANTERN_LIT->value]]);
    createWebhookEndpoint($team, ['events' => [ExampleEventType::HARBOR_FLOODED->value], 'status' => EndpointStatus::DISABLED]);

    expect(app(Webhooks::class))
        ->hasSubscribers($team, ExampleEventType::LANTERN_LIT)
        ->toBeTrue()
        ->hasSubscribers($team, ExampleEventType::HARBOR_FLOODED)
        ->toBeFalse()
        ->hasSubscribers($team->id, ExampleEventType::LANTERN_DIMMED)
        ->toBeFalse();
});

it('sends the app its own event ID and time once', function () {
    Queue::fake([SendDelivery::class]);
    $team = createTeamContextTeam('lanterns');
    createWebhookEndpoint($team);
    $id   = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    app(Webhooks::class)->dispatch($team, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1'], id: $id, occurredAt: Carbon\CarbonImmutable::parse('2026-08-23 12:07:12'));
    app(Webhooks::class)->dispatch($team, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1'], id: $id);

    $event = Event::query()->sole();
    expect($event->id)->toBe('01arz3ndektsv4rrffq69g5fav')
        ->and($event->decodedPayload())->toMatchArray([
            'id'        => 'event_01arz3ndektsv4rrffq69g5fav',
            'timestamp' => '2026-08-23T12:07:12.000Z',
        ]);
    Queue::assertPushed(SendDelivery::class, 1);
});

it('rejects an event ID that is not a ULID', function () {
    app(Webhooks::class)->dispatch(createTeamContextTeam('lanterns'), ExampleEventType::LANTERN_LIT, ['id' => 'lan_1'], id: 'evt-123');
})->throws(InvalidArgumentException::class, 'is not a ULID');

it('formats IDs with the prefixes of the app', function () {
    config(['chief.webhooks.id_prefixes' => ['endpoint' => '', 'event' => '', 'delivery' => 'dlv_']]);
    Queue::fake([SendDelivery::class]);
    $team     = createTeamContextTeam('lanterns');
    $endpoint = createWebhookEndpoint($team);

    app(Webhooks::class)->dispatch($team, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1']);

    $event    = Event::query()->sole();
    $delivery = Delivery::query()->sole();
    expect($event->decodedPayload()['id'])->toBe($event->id)
        ->and($endpoint->formatted_id)->toBe($endpoint->id)
        ->and($delivery->formatted_id)->toBe('dlv_' . $delivery->id)
        ->and(Delivery::query()->whereFormattedId('dlv_' . $delivery->id)->sole()->is($delivery))->toBeTrue()
        ->and(Endpoint::query()->whereFormattedId($endpoint->id)->sole()->is($endpoint))->toBeTrue();
});

it('keeps a delivery for every endpoint when the queue fails halfway', function () {
    $this->travelTo(Carbon\CarbonImmutable::parse('2026-11-07 12:00:00'));
    Illuminate\Support\Facades\Exceptions::fake();
    $team = createTeamContextTeam('lanterns');
    createWebhookEndpoint($team);
    createWebhookEndpoint($team);
    Illuminate\Support\Facades\Bus::shouldReceive('dispatch')->once()->andThrow(new RuntimeException('The queue is down.'));
    Illuminate\Support\Facades\Bus::shouldReceive('dispatch')->once()->andReturnNull();

    app(Webhooks::class)->dispatch($team, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1']);

    // The one that failed to queue is due right away for the sweeper, the other waits on its queued job
    expect(Delivery::query()->orderBy('next_attempt_at')->pluck('next_attempt_at')->map->format('H:i')->all())->toBe(['12:00', '12:10']);
    Illuminate\Support\Facades\Exceptions::assertReported(RuntimeException::class);
});

it('forgets the cached subscriptions once a new endpoint commits', function () {
    $team = createTeamContextTeam('lanterns');

    DB::transaction(static function () use ($team) {
        createWebhookEndpoint($team);

        // Another connection cannot see the endpoint yet and caches the list without it
        Illuminate\Support\Facades\Cache::put(Webhooks::CACHE_KEY . $team->id, [], 3600);
    });

    expect(app(Webhooks::class)->hasSubscribers($team, ExampleEventType::LANTERN_LIT))->toBeTrue();
});

it('reports a failure to record the event instead of failing the commit of the work that caused it', function () {
    Illuminate\Support\Facades\Exceptions::fake();
    $team = createTeamContextTeam('lanterns');
    createWebhookEndpoint($team);
    Event::creating(static fn () => throw new RuntimeException('The disk is full.'));

    DB::transaction(static function () use ($team) {
        app(Webhooks::class)->dispatch($team, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1']);
    });

    expect(Event::query()->count())->toBe(0);
    Illuminate\Support\Facades\Exceptions::assertReported(RuntimeException::class);
});

it('records the event as part of the caller\'s transaction and queues it once that commits', function () {
    $this->travelTo(Carbon\CarbonImmutable::parse('2026-11-07 12:00:00'));
    Queue::fake([SendDelivery::class]);
    $team = createTeamContextTeam('lanterns');
    createWebhookEndpoint($team);
    createWebhookEndpoint($team, ['events' => [ExampleEventType::HARBOR_FLOODED->value]]);

    DB::transaction(static function () use ($team) {
        expect(app(Webhooks::class)->record($team, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1']))->toBe(RecordResult::RECORDED);

        // Stored and due before the commit, so the sweeper sends it when the queueing after the commit never happens
        expect(Delivery::query()->sole()->next_attempt_at->format('H:i'))->toBe('12:00');
        Queue::assertNotPushed(SendDelivery::class);
    });

    Queue::assertPushed(SendDelivery::class, 1);
    expect(Delivery::query()->sole()->next_attempt_at->format('H:i'))->toBe('12:10');
});

it('leaves no recorded event behind when the caller\'s transaction rolls back', function () {
    Queue::fake([SendDelivery::class]);
    $team = createTeamContextTeam('lanterns');
    createWebhookEndpoint($team);

    rescue(static function () use ($team) {
        DB::transaction(static function () use ($team) {
            app(Webhooks::class)->record($team, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1']);

            throw new RuntimeException('The lantern broke.');
        });
    }, report: false);

    expect(Event::query()->count())->toBe(0)
        ->and(Delivery::query()->count())->toBe(0);
    Queue::assertNotPushed(SendDelivery::class);
});

it('throws a failure to record the event so the caller\'s transaction rolls back', function () {
    $team = createTeamContextTeam('lanterns');
    createWebhookEndpoint($team);
    Event::creating(static fn () => throw new RuntimeException('The disk is full.'));

    expect(static fn () => DB::transaction(static function () use ($team) {
        createWebhookEndpoint($team, ['description' => 'Rolled back']);

        app(Webhooks::class)->record($team, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1']);
    }))->toThrow(RuntimeException::class, 'The disk is full.');

    expect(Endpoint::query()->where('description', 'Rolled back')->exists())->toBeFalse();
});

it('records an event ID once and tells when it was recorded before', function () {
    Queue::fake([SendDelivery::class]);
    $team = createTeamContextTeam('lanterns');
    createWebhookEndpoint($team);
    $id   = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    expect(app(Webhooks::class))
        ->record($team, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1'], id: $id)
        ->toBe(RecordResult::RECORDED)
        ->record($team, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1'], id: strtoupper($id))
        ->toBe(RecordResult::ALREADY_RECORDED);

    expect(Delivery::query()->count())->toBe(1);
    Queue::assertPushed(SendDelivery::class, 1);
});

it('tells a concurrent store of the same event ID apart without failing the caller\'s transaction', function () {
    Queue::fake([SendDelivery::class]);
    $team = createTeamContextTeam('lanterns');
    createWebhookEndpoint($team);
    $id   = '01arz3ndektsv4rrffq69g5fav';

    // Another process stores the same event between the check for the ID and the insert
    $raced = false;
    Event::creating(static function () use ($team, $id, &$raced) {
        if ($raced) {
            return;
        }

        $raced = true;
        DB::table('webhook_events')->insert(['id' => $id, 'team_id' => $team->id, 'type' => 'lantern.lit', 'api_version' => 'v1', 'payload' => '{}', 'created_at' => now()]);
    });

    DB::transaction(static function () use ($team, $id) {
        expect(app(Webhooks::class)->record($team, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1'], id: $id))->toBe(RecordResult::ALREADY_RECORDED);

        createWebhookEndpoint($team, ['description' => 'Kept']);
    });

    expect(Delivery::query()->count())->toBe(0)
        ->and(Endpoint::query()->where('description', 'Kept')->exists())->toBeTrue();
    Queue::assertNotPushed(SendDelivery::class);
});

it('decides who receives a recorded event from what the transaction sees, not the cached subscriptions', function () {
    Queue::fake([SendDelivery::class]);
    $team = createTeamContextTeam('lanterns');
    createWebhookEndpoint($team, ['events' => [ExampleEventType::HARBOR_FLOODED->value]]);

    expect(app(Webhooks::class)->record($team, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1']))->toBe(RecordResult::NO_SUBSCRIBERS);

    DB::transaction(static function () use ($team) {
        createWebhookEndpoint($team);
        Illuminate\Support\Facades\Cache::put(Webhooks::CACHE_KEY . $team->id, [], 3600);

        expect(app(Webhooks::class)->record($team, ExampleEventType::LANTERN_LIT, ['id' => 'lan_2']))->toBe(RecordResult::RECORDED);
    });

    expect(Event::query()->count())->toBe(1);
});

it('records nothing while webhooks are disabled for the app', function () {
    config(['chief.webhooks.enabled' => false]);
    $team = createTeamContextTeam('lanterns');
    createWebhookEndpoint($team);

    expect(app(Webhooks::class)->record($team, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1']))->toBe(RecordResult::NO_SUBSCRIBERS)
        ->and(Event::query()->count())->toBe(0);
});

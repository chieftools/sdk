<?php

namespace ChiefTools\SDK\Webhook\Outbound;

use stdClass;
use Throwable;
use JsonSerializable;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use InvalidArgumentException;
use ChiefTools\SDK\Entities\Team;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use ChiefTools\SDK\Webhook\Outbound\Models\Event;
use ChiefTools\SDK\Webhook\Outbound\Models\Delivery;
use ChiefTools\SDK\Webhook\Outbound\Models\Endpoint;
use ChiefTools\SDK\Webhook\Outbound\Jobs\SendDelivery;
use ChiefTools\SDK\Webhook\Outbound\Enums\BuiltInEvent;
use ChiefTools\SDK\Webhook\Outbound\Enums\RecordResult;
use ChiefTools\SDK\Webhook\Outbound\Enums\DeliveryStatus;
use ChiefTools\SDK\Webhook\Outbound\Enums\EndpointStatus;
use Illuminate\Database\UniqueConstraintViolationException;
use ChiefTools\SDK\Webhook\Outbound\Contracts\WebhookEventType;

/**
 * Sends the app's events to the endpoints its teams subscribed, the entry point for apps.
 *
 * An event is stored once with the exact body every endpoint receives, then each subscribed endpoint gets a delivery
 * that `\ChiefTools\SDK\Webhook\Outbound\Deliverer` attempts on the queue and retries on `chief.webhooks.retry_schedule`.
 */
class Webhooks
{
    /** How long an attempt may sit on the queue before `chief:webhooks:dispatch-due` queues it again. */
    public const int QUEUE_LEASE_SECONDS = 600;

    /** The cache key prefix of a team's subscribed endpoints, followed by the team's ID. */
    public const string CACHE_KEY = 'chief-webhooks:endpoints:';

    private const int CACHE_SECONDS = 3600;

    /**
     * Sends an event to the team's endpoints that subscribed to it, once the current transaction commits. Does nothing
     * without such an endpoint, so apps can call it for every change without checking first.
     *
     * The object and data are encoded right away, so an API resource shows the state at the time of the call.
     *
     * Apps that already keep their events, like an event feed in their API, pass the event's ULID and time so a
     * receiver sees the same ID and time in both. An ID that was sent before is not sent again.
     *
     * @param array<string, mixed>|\JsonSerializable $object     the API representation of what the event is about, as `data.object`
     * @param array<string, mixed>                   $data       more fields next to the object in `data`, like why an alert resolved
     * @param string|null                            $id         the ULID of the app's own record of the event
     * @param \Carbon\CarbonInterface|null           $occurredAt when it happened, now by default
     *
     * @throws \InvalidArgumentException when the ID is not a ULID
     */
    public function dispatch(Team|int $team, WebhookEventType $type, array|JsonSerializable $object, array $data = [], ?string $id = null, ?CarbonInterface $occurredAt = null): void
    {
        $id = self::normalizeId($id);

        if (!$this->hasSubscribers($team, $type)) {
            return;
        }

        $teamId = $team instanceof Team ? $team->getKey() : $team;

        // The event happened now, even when the transaction commits later
        $occurredAt ??= now();
        $snapshot     = self::snapshot($object, $data);

        // Recorded once the caller's work commits, which is done by then: a failure here is reported, not thrown at the
        // caller's commit as if that work had failed
        self::connection()->afterCommit(function () use ($teamId, $type, $snapshot, $occurredAt, $id) {
            try {
                $this->store($teamId, $type, $snapshot, $occurredAt, $id);
            } catch (Throwable $e) {
                report($e);
            }
        });
    }

    /**
     * Stores an event with a pending delivery for every endpoint that receives it right away, as part of the caller's
     * transaction, for apps that must not commit their own record of an event without its webhooks.
     *
     * Unlike `dispatch()` a failure to store is thrown so the caller's transaction rolls back, and a rolled back
     * transaction leaves nothing behind. The first attempts are queued once the transaction commits; when that queueing
     * fails or never happens the deliveries are already due, so `chief:webhooks:dispatch-due` sends them within a minute.
     *
     * The webhook tables are written on the connection of the webhook models, the default connection, so the caller's
     * transaction has to be on that connection for both to commit together. The stored rows lock the endpoints they
     * refer to until that transaction ends, so keep it short.
     *
     * @param array<string, mixed>|\JsonSerializable $object     the API representation of what the event is about, as `data.object`
     * @param array<string, mixed>                   $data       more fields next to the object in `data`
     * @param string|null                            $id         the ULID of the app's own record of the event, an ID that was stored before is not stored again
     * @param \Carbon\CarbonInterface|null           $occurredAt when it happened, now by default
     *
     * @throws \InvalidArgumentException when the ID is not a ULID
     * @throws \Throwable                when the event could not be stored
     */
    public function record(Team|int $team, WebhookEventType $type, array|JsonSerializable $object, array $data = [], ?string $id = null, ?CarbonInterface $occurredAt = null): RecordResult
    {
        $id = self::normalizeId($id);

        if (!WebhookConfig::enabled() || $type instanceof BuiltInEvent) {
            return RecordResult::NO_SUBSCRIBERS;
        }

        return $this->store($team instanceof Team ? $team->getKey() : $team, $type, self::snapshot($object, $data), $occurredAt ?? now(), $id);
    }

    /**
     * Stores the event with a delivery for every endpoint that receives it and queues their first attempts once the
     * current transaction commits. The endpoints are read from the database, not the cached subscriptions, so the
     * transaction decides with what it sees itself.
     */
    private function store(int $teamId, WebhookEventType $type, stdClass $snapshot, CarbonInterface $occurredAt, ?string $id): RecordResult
    {
        if ($id !== null && Event::query()->whereKey($id)->exists()) {
            return RecordResult::ALREADY_RECORDED;
        }

        $endpoints = Endpoint::query()
            ->where('team_id', $teamId)
            ->where('status', EndpointStatus::ENABLED)
            ->get()
            ->filter(static fn (Endpoint $endpoint): bool => $endpoint->isSubscribedTo($type));

        if ($endpoints->isEmpty()) {
            return RecordResult::NO_SUBSCRIBERS;
        }

        // Every delivery exists before any is queued, so a queue that fails halfway loses none of them. Inside the
        // caller's transaction this is a savepoint, so a duplicate ID rolls back only these rows and not the caller's
        try {
            $deliveries = self::connection()->transaction(function () use ($teamId, $type, $snapshot, $occurredAt, $id, $endpoints): array {
                $event = $this->recordEvent($teamId, $type, $snapshot, $occurredAt, $id);

                return $endpoints->map(fn (Endpoint $endpoint): Delivery => $this->createDelivery($event, $endpoint))->values()->all();
            });
        } catch (UniqueConstraintViolationException) {
            // Another call with the same ID got there first
            return RecordResult::ALREADY_RECORDED;
        }

        // The deliveries are committed by then: a failure to queue is reported, they are due for the sweeper already
        self::connection()->afterCommit(function () use ($deliveries) {
            foreach ($deliveries as $delivery) {
                try {
                    $this->queueAttempt($delivery);
                } catch (Throwable $e) {
                    report($e);
                }
            }
        });

        return RecordResult::RECORDED;
    }

    /** Whether any enabled endpoint of the team receives the event, so apps can skip building an expensive payload. */
    public function hasSubscribers(Team|int $team, WebhookEventType $type): bool
    {
        if (!WebhookConfig::enabled() || $type instanceof BuiltInEvent) {
            return false;
        }

        return $this->subscribedEndpointIds($team instanceof Team ? $team->getKey() : $team, $type) !== [];
    }

    /**
     * Sends an event to one endpoint whether or not it subscribed, for test events.
     *
     * @param array<string, mixed>|\JsonSerializable $object
     * @param array<string, mixed>                   $data
     */
    public function sendTo(Endpoint $endpoint, WebhookEventType $type, array|JsonSerializable $object, array $data = []): Delivery
    {
        return $this->deliver($this->recordEvent($endpoint->team_id, $type, self::snapshot($object, $data), now()), $endpoint);
    }

    /** Creates a delivery of the event to the endpoint and queues its first attempt. */
    public function deliver(Event $event, Endpoint $endpoint): Delivery
    {
        $delivery = $this->createDelivery($event, $endpoint);

        $this->queueAttempt($delivery);

        return $delivery;
    }

    /**
     * Queues the next attempt of a pending delivery, pushing its due time ahead so the sweeper leaves it alone meanwhile.
     * When the queue fails the delivery is due again right away, so `chief:webhooks:dispatch-due` queues it instead.
     */
    public function queueAttempt(Delivery $delivery): void
    {
        $delivery->forceFill(['next_attempt_at' => now()->addSeconds(self::QUEUE_LEASE_SECONDS)])->save();

        try {
            Bus::dispatch(new SendDelivery($delivery->getKey(), $delivery->attempts));
        } catch (Throwable $e) {
            report($e);

            $delivery->forceFill(['next_attempt_at' => now()])->save();
        }
    }

    /**
     * Queues an attempt someone asked for on its own clock, `manual_attempt_at`, so its scheduled automatic attempt
     * keeps its due time. When the queue fails the resend is due right away for `chief:webhooks:dispatch-due` instead.
     */
    public function queueManualAttempt(Delivery $delivery): void
    {
        $delivery->forceFill(['manual_attempt_at' => now()->addSeconds(self::QUEUE_LEASE_SECONDS)])->save();

        try {
            Bus::dispatch(new SendDelivery($delivery->getKey(), $delivery->attempts, manual: true));
        } catch (Throwable $e) {
            report($e);

            $delivery->forceFill(['manual_attempt_at' => now()])->save();
        }
    }

    /** A pending delivery of the event to the endpoint, due right away until its first attempt is queued. */
    private function createDelivery(Event $event, Endpoint $endpoint): Delivery
    {
        return Delivery::query()->create([
            'team_id'         => $event->team_id,
            'endpoint_id'     => $endpoint->getKey(),
            'event_id'        => $event->getKey(),
            'status'          => DeliveryStatus::PENDING,
            'attempts'        => 0,
            'next_attempt_at' => now(),
        ]);
    }

    /**
     * The events endpoints can subscribe to: the cases of the app's enum.
     *
     * @return list<\ChiefTools\SDK\Webhook\Outbound\Contracts\WebhookEventType>
     */
    public function eventTypes(): array
    {
        $enum = WebhookConfig::eventsEnum();

        return $enum === null ? [] : $enum::cases();
    }

    /**
     * The event types grouped by their heading, for choosing events.
     *
     * @return array<string, list<\ChiefTools\SDK\Webhook\Outbound\Contracts\WebhookEventType>>
     */
    public function groupedEventTypes(): array
    {
        $groups = [];

        foreach ($this->eventTypes() as $type) {
            $groups[$type->group()][] = $type;
        }

        return $groups;
    }

    public function forgetEndpoints(int $teamId): void
    {
        Cache::forget(self::CACHE_KEY . $teamId);
    }

    /** @return list<string> */
    private function subscribedEndpointIds(int $teamId, WebhookEventType $type): array
    {
        /** @var array<string, list<string>> $endpoints */
        $endpoints = Cache::remember(
            self::CACHE_KEY . $teamId,
            self::CACHE_SECONDS,
            static fn (): array => Endpoint::query()
                ->where('team_id', $teamId)
                ->where('status', EndpointStatus::ENABLED)
                ->get(['id', 'events'])
                ->mapWithKeys(static fn (Endpoint $endpoint) => [$endpoint->getKey() => $endpoint->events])
                ->all(),
        );

        return array_keys(array_filter(
            $endpoints,
            static fn (array $events): bool => in_array(Endpoint::ALL_EVENTS, $events, true) || in_array($type->value, $events, true),
        ));
    }

    /**
     * The event's `data` as plain JSON values, decoded to objects so an empty object stays `{}` in the payload.
     *
     * @param array<string, mixed>|\JsonSerializable $object
     * @param array<string, mixed>                   $data
     */
    private static function snapshot(array|JsonSerializable $object, array $data): stdClass
    {
        return json_decode(json_encode(['object' => $object] + $data, JSON_THROW_ON_ERROR), false, flags: JSON_THROW_ON_ERROR);
    }

    /** @throws \InvalidArgumentException when the ID is not a ULID */
    private static function normalizeId(?string $id): ?string
    {
        if ($id !== null && !Str::isUlid($id)) {
            throw new InvalidArgumentException("The webhook event ID [{$id}] is not a ULID.");
        }

        return $id === null ? null : strtolower($id);
    }

    /** The connection the webhook tables are written on, so their transaction and the caller's are the same one. */
    private static function connection(): Connection
    {
        return (new Event)->getConnection();
    }

    private function recordEvent(int $teamId, WebhookEventType $type, stdClass $data, CarbonInterface $occurredAt, ?string $id = null): Event
    {
        $event = new Event;
        $event->setAttribute('id', $id ?? $event->newUniqueId());

        $payload = json_encode([
            'id'          => $event->formatted_id,
            'type'        => $type->value,
            'timestamp'   => $occurredAt->toIso8601ZuluString('millisecond'),
            'api_version' => WebhookConfig::apiVersion(),
            'data'        => $data,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $event->forceFill([
            'team_id'     => $teamId,
            'type'        => $type->value,
            'api_version' => WebhookConfig::apiVersion(),
            'payload'     => $payload,
            'created_at'  => $occurredAt,
        ])->save();

        return $event;
    }
}

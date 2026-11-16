<?php

namespace ChiefTools\SDK\Webhook\Outbound\Testing;

use ChiefTools\SDK\Entities\Team;
use Illuminate\Support\Facades\Http;
use ChiefTools\SDK\Webhook\Outbound\Models\Event;
use ChiefTools\SDK\Webhook\Outbound\Signing\Signer;
use ChiefTools\SDK\Webhook\Outbound\Models\Endpoint;
use ChiefTools\SDK\Webhook\Outbound\Security\UrlGuard;
use ChiefTools\SDK\Webhook\Outbound\Enums\EndpointStatus;
use ChiefTools\SDK\Webhook\Outbound\Contracts\WebhookEventType;

/**
 * Helpers for app tests that check which webhooks a change sends, so every app tests its events the same way.
 */
final class Webhooks
{
    /** The endpoint URL of `subscribe()`, its host resolves to a public address and answers every delivery with a 200. */
    public const string ENDPOINT_URL = 'https://hooks.chief.test/webhooks';

    /**
     * Gives the team an enabled endpoint that receives the events, all of them by default, without touching DNS or the
     * network: its host resolves to a public address and the HTTP client answers its deliveries with a 200.
     *
     * @param list<string|\ChiefTools\SDK\Webhook\Outbound\Contracts\WebhookEventType> $events
     */
    public static function subscribe(Team|int $team, array $events = [Endpoint::ALL_EVENTS]): Endpoint
    {
        app()->instance(UrlGuard::class, new UrlGuard(static fn (): array => ['93.184.215.14']));

        Http::fake([parse_url(self::ENDPOINT_URL, PHP_URL_HOST) . '/*' => Http::response()]);

        return Endpoint::query()->create([
            'team_id' => $team instanceof Team ? $team->getKey() : $team,
            'url'     => self::ENDPOINT_URL,
            'events'  => array_map(static fn (string|WebhookEventType $event): string => $event instanceof WebhookEventType ? (string)$event->value : $event, $events),
            'secret'  => Signer::generateSecret(),
            'status'  => EndpointStatus::ENABLED,
        ]);
    }

    /**
     * The payloads of the events that were stored for the endpoints, oldest first, optionally of one type. Each has the
     * `id`, `type`, `timestamp`, `api_version` and `data` an endpoint receives.
     *
     * @return list<array<string, mixed>>
     */
    public static function sent(?WebhookEventType $type = null): array
    {
        return Event::query()
            ->when($type !== null, static fn ($query) => $query->where('type', $type->value))
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(static fn (Event $event): array => $event->decodedPayload())
            ->values()
            ->all();
    }
}

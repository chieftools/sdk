<?php

namespace ChiefTools\SDK\Webhook\Outbound\Actions;

use ChiefTools\SDK\Webhook\Outbound\Webhooks;
use Illuminate\Validation\ValidationException;
use ChiefTools\SDK\Webhook\Outbound\Models\Event;
use ChiefTools\SDK\Webhook\Outbound\Models\Delivery;
use ChiefTools\SDK\Webhook\Outbound\Models\Endpoint;

/**
 * Sends an event to an endpoint again: as the next attempt of its delivery there, or as a first delivery to an endpoint
 * that never received it. The event ID stays the same, so receivers that store the IDs they processed can skip it.
 */
class ResendEvent
{
    public function __construct(
        private readonly Webhooks $webhooks,
        private readonly ResendDelivery $resendDelivery,
    ) {}

    /** @throws \Illuminate\Validation\ValidationException */
    public function handle(Event $event, Endpoint $endpoint): Delivery
    {
        if ($event->team_id !== $endpoint->team_id) {
            throw ValidationException::withMessages([
                'endpoint' => 'The event belongs to another team.',
            ]);
        }

        if (!$endpoint->isEnabled()) {
            throw ValidationException::withMessages([
                'endpoint' => 'Enable the endpoint to resend events to it.',
            ]);
        }

        $delivery = $event->deliveries()->where('endpoint_id', $endpoint->getKey())->latest()->first();

        return $delivery === null
            ? $this->webhooks->deliver($event, $endpoint)
            : $this->resendDelivery->handle($delivery);
    }
}

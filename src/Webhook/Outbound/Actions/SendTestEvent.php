<?php

namespace ChiefTools\SDK\Webhook\Outbound\Actions;

use ChiefTools\SDK\Webhook\Outbound\Webhooks;
use Illuminate\Validation\ValidationException;
use ChiefTools\SDK\Webhook\Outbound\Models\Delivery;
use ChiefTools\SDK\Webhook\Outbound\Models\Endpoint;
use ChiefTools\SDK\Webhook\Outbound\Enums\BuiltInEvent;

/**
 * Sends a `webhook_endpoint.test` event to one endpoint, to check it receives and verifies deliveries.
 */
class SendTestEvent
{
    public function __construct(
        private readonly Webhooks $webhooks,
    ) {}

    /** @throws \Illuminate\Validation\ValidationException */
    public function handle(Endpoint $endpoint): Delivery
    {
        if (!$endpoint->isEnabled()) {
            throw ValidationException::withMessages([
                'endpoint' => 'Enable the endpoint to send it a test event.',
            ]);
        }

        return $this->webhooks->sendTo($endpoint, BuiltInEvent::TEST, [
            'id'          => $endpoint->formatted_id,
            'url'         => $endpoint->url,
            'description' => $endpoint->description,
        ]);
    }
}

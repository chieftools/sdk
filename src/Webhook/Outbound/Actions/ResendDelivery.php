<?php

namespace ChiefTools\SDK\Webhook\Outbound\Actions;

use ChiefTools\SDK\Webhook\Outbound\Webhooks;
use Illuminate\Validation\ValidationException;
use ChiefTools\SDK\Webhook\Outbound\Models\Delivery;

/**
 * Sends a delivery again right away as its next attempt, whatever its state. It does not use up the automatic retries:
 * while they are scheduled a failed resend keeps the next one, a successful resend ends them.
 */
class ResendDelivery
{
    public function __construct(
        private readonly Webhooks $webhooks,
    ) {}

    /** @throws \Illuminate\Validation\ValidationException */
    public function handle(Delivery $delivery): Delivery
    {
        $delivery->loadMissing('endpoint');

        if (!$delivery->endpoint->isEnabled()) {
            throw ValidationException::withMessages([
                'delivery' => 'Enable the endpoint to resend its deliveries.',
            ]);
        }

        $this->webhooks->queueManualAttempt($delivery);

        return $delivery;
    }
}

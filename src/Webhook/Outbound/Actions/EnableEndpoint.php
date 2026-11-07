<?php

namespace ChiefTools\SDK\Webhook\Outbound\Actions;

use ChiefTools\SDK\Webhook\Outbound\Models\Endpoint;
use ChiefTools\SDK\Webhook\Outbound\Enums\EndpointStatus;

/**
 * Starts sending to an endpoint again with a clean failure record, deliveries cancelled while it was disabled stay
 * cancelled and can be resent.
 */
class EnableEndpoint
{
    public function handle(Endpoint $endpoint): Endpoint
    {
        $endpoint->forceFill([
            'status'              => EndpointStatus::ENABLED,
            'disabled_reason'     => null,
            'disabled_at'         => null,
            'failing_since'       => null,
            'failure_notified_at' => null,
        ])->save();

        return $endpoint;
    }
}

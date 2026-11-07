<?php

namespace ChiefTools\SDK\Webhook\Outbound\Actions;

use ChiefTools\SDK\Webhook\Outbound\Models\Endpoint;

/**
 * Removes an endpoint with its deliveries and their attempts, the events stay for the team's other endpoints.
 */
class DeleteEndpoint
{
    public function handle(Endpoint $endpoint): void
    {
        $endpoint->delete();
    }
}

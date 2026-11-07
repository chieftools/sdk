<?php

namespace ChiefTools\SDK\Webhook\Outbound\Http\Controllers\Endpoints;

use Dedoc\Scramble\Attributes\Group;
use ChiefTools\SDK\Webhook\Outbound\Actions\DisableEndpoint;
use ChiefTools\SDK\Webhook\Outbound\Http\Resources\EndpointResource;
use ChiefTools\SDK\Webhook\Outbound\Http\Concerns\ResolvesWebhookTeam;

#[Group('Webhooks')]
class Disable
{
    use ResolvesWebhookTeam;

    /**
     * Disable a webhook endpoint.
     *
     * Stop sending events to an endpoint. Its deliveries waiting for a retry are cancelled.
     *
     * @operationId webhooks.endpoints.disable
     */
    public function __invoke(DisableEndpoint $disableEndpoint, string $endpoint): EndpointResource
    {
        return new EndpointResource($disableEndpoint->handle($this->findEndpoint($endpoint)));
    }
}

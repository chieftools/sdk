<?php

namespace ChiefTools\SDK\Webhook\Outbound\Http\Controllers\Endpoints;

use Dedoc\Scramble\Attributes\Group;
use ChiefTools\SDK\Webhook\Outbound\Http\Resources\EndpointResource;
use ChiefTools\SDK\Webhook\Outbound\Http\Concerns\ResolvesWebhookTeam;

#[Group('Webhooks')]
class Single
{
    use ResolvesWebhookTeam;

    /**
     * Get a webhook endpoint.
     *
     * Get an endpoint of the team. Its signing secret is not included.
     *
     * @operationId webhooks.endpoints.single
     */
    public function __invoke(string $endpoint): EndpointResource
    {
        return new EndpointResource($this->findEndpoint($endpoint));
    }
}

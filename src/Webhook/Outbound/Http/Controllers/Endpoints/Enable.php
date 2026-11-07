<?php

namespace ChiefTools\SDK\Webhook\Outbound\Http\Controllers\Endpoints;

use Dedoc\Scramble\Attributes\Group;
use ChiefTools\SDK\Webhook\Outbound\Actions\EnableEndpoint;
use ChiefTools\SDK\Webhook\Outbound\Http\Resources\EndpointResource;
use ChiefTools\SDK\Webhook\Outbound\Http\Concerns\ResolvesWebhookTeam;

#[Group('Webhooks')]
class Enable
{
    use ResolvesWebhookTeam;

    /**
     * Enable a webhook endpoint.
     *
     * Start sending events to a disabled endpoint again. Events that happened while it was disabled are not sent, resend them from its deliveries or the events.
     *
     * @operationId webhooks.endpoints.enable
     */
    public function __invoke(EnableEndpoint $enableEndpoint, string $endpoint): EndpointResource
    {
        return new EndpointResource($enableEndpoint->handle($this->findEndpoint($endpoint)));
    }
}

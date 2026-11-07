<?php

namespace ChiefTools\SDK\Webhook\Outbound\Http\Controllers\Endpoints;

use Illuminate\Http\Response;
use Dedoc\Scramble\Attributes\Group;
use ChiefTools\SDK\Webhook\Outbound\Actions\DeleteEndpoint;
use ChiefTools\SDK\Webhook\Outbound\Http\Concerns\ResolvesWebhookTeam;

#[Group('Webhooks')]
class Delete
{
    use ResolvesWebhookTeam;

    /**
     * Delete a webhook endpoint.
     *
     * Delete an endpoint with its delivery log. Deliveries still waiting for an attempt are not sent.
     *
     * @operationId webhooks.endpoints.delete
     */
    public function __invoke(DeleteEndpoint $deleteEndpoint, string $endpoint): Response
    {
        $deleteEndpoint->handle($this->findEndpoint($endpoint));

        return response()->noContent();
    }
}

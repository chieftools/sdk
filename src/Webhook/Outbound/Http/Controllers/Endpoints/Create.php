<?php

namespace ChiefTools\SDK\Webhook\Outbound\Http\Controllers\Endpoints;

use Illuminate\Http\JsonResponse;
use Dedoc\Scramble\Attributes\Group;
use ChiefTools\SDK\Webhook\Outbound\Actions\CreateEndpoint;
use ChiefTools\SDK\Webhook\Outbound\Http\Resources\EndpointResource;
use ChiefTools\SDK\Webhook\Outbound\Http\Concerns\ResolvesWebhookTeam;
use ChiefTools\SDK\Webhook\Outbound\Http\Requests\CreateWebhookEndpointRequest;

#[Group('Webhooks')]
class Create
{
    use ResolvesWebhookTeam;

    /**
     * Create a webhook endpoint.
     *
     * Add an endpoint that receives the chosen events. The response is the only time its signing secret is returned until it is rotated. Fails with 422 when the team's plan has no room for another endpoint.
     *
     * @operationId webhooks.endpoints.create
     */
    public function __invoke(CreateWebhookEndpointRequest $request, CreateEndpoint $createEndpoint): JsonResponse
    {
        return (new EndpointResource($createEndpoint->handle($this->webhookTeam(), $request->validated())))->withSecret()->response()->setStatusCode(201);
    }
}

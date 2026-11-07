<?php

namespace ChiefTools\SDK\Webhook\Outbound\Http\Controllers\Endpoints;

use Dedoc\Scramble\Attributes\Group;
use ChiefTools\SDK\Webhook\Outbound\Actions\UpdateEndpoint;
use ChiefTools\SDK\Webhook\Outbound\Http\Resources\EndpointResource;
use ChiefTools\SDK\Webhook\Outbound\Http\Concerns\ResolvesWebhookTeam;
use ChiefTools\SDK\Webhook\Outbound\Http\Requests\UpdateWebhookEndpointRequest;

#[Group('Webhooks')]
class Update
{
    use ResolvesWebhookTeam;

    /**
     * Update a webhook endpoint.
     *
     * Change an endpoint's URL, description or events. Fields left out keep their value.
     *
     * @operationId webhooks.endpoints.update
     */
    public function __invoke(UpdateWebhookEndpointRequest $request, UpdateEndpoint $updateEndpoint, string $endpoint): EndpointResource
    {
        $model = $this->findEndpoint($endpoint);

        return new EndpointResource($updateEndpoint->handle($model, [
            'url'         => $model->url,
            'description' => $model->description,
            'events'      => $model->events,
            ...$request->validated(),
        ]));
    }
}

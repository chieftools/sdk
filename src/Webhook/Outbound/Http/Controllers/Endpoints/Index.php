<?php

namespace ChiefTools\SDK\Webhook\Outbound\Http\Controllers\Endpoints;

use Dedoc\Scramble\Attributes\Group;
use ChiefTools\SDK\Webhook\Outbound\Models\Endpoint;
use ChiefTools\SDK\Webhook\Outbound\Http\Requests\ListRequest;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use ChiefTools\SDK\Webhook\Outbound\Http\Resources\EndpointResource;
use ChiefTools\SDK\Webhook\Outbound\Http\Concerns\ResolvesWebhookTeam;

#[Group('Webhooks', 'Endpoints for the team\'s webhook endpoints and the events and deliveries sent to them. Webhooks send a signed request to an endpoint when something happens in the team, see the webhooks guide for verifying them.', weight: 80)]
class Index
{
    use ResolvesWebhookTeam;

    /**
     * List webhook endpoints.
     *
     * List the team's webhook endpoints, oldest first. Signing secrets are not included.
     *
     * @operationId webhooks.endpoints.index
     */
    public function __invoke(ListRequest $request): AnonymousResourceCollection
    {
        $endpoints = Endpoint::query()
            ->where('team_id', $this->webhookTeam()->getKey())
            ->orderBy('created_at')
            ->orderBy('id')
            ->paginate($request->perPage());

        return EndpointResource::collection($endpoints);
    }
}

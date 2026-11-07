<?php

namespace ChiefTools\SDK\Webhook\Outbound\Http\Controllers\EventTypes;

use Dedoc\Scramble\Attributes\Group;
use ChiefTools\SDK\Webhook\Outbound\Webhooks;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use ChiefTools\SDK\Webhook\Outbound\Http\Resources\EventTypeResource;
use ChiefTools\SDK\Webhook\Outbound\Http\Concerns\ResolvesWebhookTeam;

#[Group('Webhooks')]
class Index
{
    use ResolvesWebhookTeam;

    /**
     * List webhook event types.
     *
     * List the event types endpoints can subscribe to, with the heading they are grouped under and when they are sent.
     *
     * @operationId webhooks.eventTypes.index
     */
    public function __invoke(Webhooks $webhooks): AnonymousResourceCollection
    {
        $this->webhookTeam();

        return EventTypeResource::collection($webhooks->eventTypes());
    }
}

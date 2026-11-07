<?php

namespace ChiefTools\SDK\Webhook\Outbound\Http\Controllers\Events;

use Dedoc\Scramble\Attributes\Group;
use ChiefTools\SDK\Webhook\Outbound\Http\Resources\EventResource;
use ChiefTools\SDK\Webhook\Outbound\Http\Concerns\ResolvesWebhookTeam;

#[Group('Webhooks')]
class Single
{
    use ResolvesWebhookTeam;

    /**
     * Get a webhook event.
     *
     * Get an event with its payload and its delivery to each endpoint.
     *
     * @operationId webhooks.events.single
     */
    public function __invoke(string $event): EventResource
    {
        return new EventResource($this->findEvent($event)->load(['deliveries.endpoint:id', 'deliveries.event:id,type']));
    }
}

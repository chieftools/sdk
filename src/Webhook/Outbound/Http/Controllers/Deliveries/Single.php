<?php

namespace ChiefTools\SDK\Webhook\Outbound\Http\Controllers\Deliveries;

use Dedoc\Scramble\Attributes\Group;
use ChiefTools\SDK\Webhook\Outbound\Http\Resources\DeliveryResource;
use ChiefTools\SDK\Webhook\Outbound\Http\Concerns\ResolvesWebhookTeam;

#[Group('Webhooks')]
class Single
{
    use ResolvesWebhookTeam;

    /**
     * Get a webhook delivery.
     *
     * Get a delivery with every attempt: the request headers that were sent and the response the endpoint gave.
     *
     * @operationId webhooks.deliveries.single
     */
    public function __invoke(string $delivery): DeliveryResource
    {
        return new DeliveryResource($this->findDelivery($delivery)->load(['endpoint:id', 'event:id,type', 'attemptLog']));
    }
}

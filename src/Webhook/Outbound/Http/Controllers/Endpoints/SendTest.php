<?php

namespace ChiefTools\SDK\Webhook\Outbound\Http\Controllers\Endpoints;

use Illuminate\Http\JsonResponse;
use Dedoc\Scramble\Attributes\Group;
use ChiefTools\SDK\Webhook\Outbound\Actions\SendTestEvent;
use ChiefTools\SDK\Webhook\Outbound\Http\Resources\DeliveryResource;
use ChiefTools\SDK\Webhook\Outbound\Http\Concerns\ResolvesWebhookTeam;

#[Group('Webhooks')]
class SendTest
{
    use ResolvesWebhookTeam;

    /**
     * Send a test event.
     *
     * Send a `webhook_endpoint.test` event to an enabled endpoint, to check that it receives and verifies deliveries. The delivery is queued, get it later to see how it went.
     *
     * @operationId webhooks.endpoints.sendTest
     */
    public function __invoke(SendTestEvent $sendTestEvent, string $endpoint): JsonResponse
    {
        $delivery = $sendTestEvent->handle($this->findEndpoint($endpoint))->load(['endpoint', 'event']);

        return (new DeliveryResource($delivery))->response()->setStatusCode(202);
    }
}

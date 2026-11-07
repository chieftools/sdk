<?php

namespace ChiefTools\SDK\Webhook\Outbound\Http\Controllers\Deliveries;

use Illuminate\Http\JsonResponse;
use Dedoc\Scramble\Attributes\Group;
use ChiefTools\SDK\Webhook\Outbound\Actions\ResendDelivery;
use ChiefTools\SDK\Webhook\Outbound\Http\Resources\DeliveryResource;
use ChiefTools\SDK\Webhook\Outbound\Http\Concerns\ResolvesWebhookTeam;

#[Group('Webhooks')]
class Resend
{
    use ResolvesWebhookTeam;

    /**
     * Resend a webhook delivery.
     *
     * Send a delivery again, whatever its state, as its next attempt with the same event ID. A resend does not use up automatic retries. The endpoint must be enabled.
     *
     * @operationId webhooks.deliveries.resend
     */
    public function __invoke(ResendDelivery $resendDelivery, string $delivery): JsonResponse
    {
        $resent = $resendDelivery->handle($this->findDelivery($delivery))->load(['endpoint:id', 'event:id,type']);

        return (new DeliveryResource($resent))->response()->setStatusCode(202);
    }
}

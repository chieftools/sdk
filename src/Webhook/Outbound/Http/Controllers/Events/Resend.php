<?php

namespace ChiefTools\SDK\Webhook\Outbound\Http\Controllers\Events;

use Illuminate\Http\JsonResponse;
use Dedoc\Scramble\Attributes\Group;
use ChiefTools\SDK\Webhook\Outbound\Actions\ResendEvent;
use ChiefTools\SDK\Webhook\Outbound\Http\Resources\DeliveryResource;
use ChiefTools\SDK\Webhook\Outbound\Http\Concerns\ResolvesWebhookTeam;
use ChiefTools\SDK\Webhook\Outbound\Http\Requests\ResendWebhookEventRequest;

#[Group('Webhooks')]
class Resend
{
    use ResolvesWebhookTeam;

    /**
     * Resend a webhook event.
     *
     * Send an event to one of the team's enabled endpoints, as the next attempt of its delivery there or as a new delivery when it has none. Use it to catch an endpoint up after it was disabled.
     *
     * @operationId webhooks.events.resend
     */
    public function __invoke(ResendWebhookEventRequest $request, ResendEvent $resendEvent, string $event): JsonResponse
    {
        $delivery = $resendEvent->handle($this->findEvent($event), $this->findEndpoint($request->string('endpoint')->toString()))->load(['endpoint:id', 'event:id,type']);

        return (new DeliveryResource($delivery))->response()->setStatusCode(202);
    }
}

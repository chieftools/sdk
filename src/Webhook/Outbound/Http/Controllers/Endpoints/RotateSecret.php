<?php

namespace ChiefTools\SDK\Webhook\Outbound\Http\Controllers\Endpoints;

use Dedoc\Scramble\Attributes\Group;
use ChiefTools\SDK\Webhook\Outbound\Http\Resources\EndpointResource;
use ChiefTools\SDK\Webhook\Outbound\Http\Concerns\ResolvesWebhookTeam;
use ChiefTools\SDK\Webhook\Outbound\Http\Requests\RotateWebhookSecretRequest;
use ChiefTools\SDK\Webhook\Outbound\Actions\RotateSecret as RotateEndpointSecret;

#[Group('Webhooks')]
class RotateSecret
{
    use ResolvesWebhookTeam;

    /**
     * Rotate a webhook endpoint's secret.
     *
     * Replace the signing secret and return the new one. For the next 24 hours deliveries are signed with both, so receivers can switch without rejecting any. Fails with 422 while the secret from the last rotation still signs, unless `replace_previous` is true.
     *
     * @operationId webhooks.endpoints.rotateSecret
     */
    public function __invoke(RotateWebhookSecretRequest $request, RotateEndpointSecret $rotateSecret, string $endpoint): EndpointResource
    {
        $rotated = $rotateSecret->handle($this->findEndpoint($endpoint), replacePrevious: $request->boolean('replace_previous'));

        return (new EndpointResource($rotated))->withSecret();
    }
}

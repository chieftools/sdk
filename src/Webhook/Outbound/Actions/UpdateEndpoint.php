<?php

namespace ChiefTools\SDK\Webhook\Outbound\Actions;

use ChiefTools\SDK\Webhook\Outbound\Models\Endpoint;

class UpdateEndpoint
{
    /**
     * @param array<string, mixed> $input `url`, `description` and `events`, see `EndpointInput::rules()`
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function handle(Endpoint $endpoint, array $input): Endpoint
    {
        $endpoint->fill(EndpointInput::validate($input))->save();

        return $endpoint;
    }
}

<?php

namespace ChiefTools\SDK\Webhook\Outbound\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ChiefTools\SDK\Webhook\Outbound\Models\Endpoint;

/**
 * An endpoint keeps failing: a delivery ran out of attempts or every attempt failed for an hour. Fired at most once a
 * day per endpoint, apps let the team know.
 */
class EndpointFailing
{
    use Dispatchable;

    public function __construct(
        public readonly Endpoint $endpoint,
    ) {}
}

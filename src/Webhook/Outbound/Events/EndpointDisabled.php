<?php

namespace ChiefTools\SDK\Webhook\Outbound\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ChiefTools\SDK\Webhook\Outbound\Models\Endpoint;

/**
 * An endpoint was disabled because every delivery failed for `chief.webhooks.disable_after_days` days, apps let the
 * team know. Not fired when someone disables it.
 */
class EndpointDisabled
{
    use Dispatchable;

    public function __construct(
        public readonly Endpoint $endpoint,
    ) {}
}

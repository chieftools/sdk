<?php

namespace ChiefTools\SDK\Webhook\Outbound\Actions;

use Illuminate\Support\Facades\DB;
use ChiefTools\SDK\Webhook\Outbound\Models\Endpoint;
use ChiefTools\SDK\Webhook\Outbound\Enums\DeliveryStatus;
use ChiefTools\SDK\Webhook\Outbound\Enums\DisabledReason;
use ChiefTools\SDK\Webhook\Outbound\Enums\EndpointStatus;

/**
 * Stops sending to an endpoint and cancels its pending deliveries, which can be resent after enabling it again. Events
 * that happen while it is disabled get no delivery for it.
 */
class DisableEndpoint
{
    public function handle(Endpoint $endpoint, DisabledReason $reason = DisabledReason::MANUAL): Endpoint
    {
        DB::transaction(static function () use ($endpoint, $reason) {
            $endpoint->forceFill([
                'status'          => EndpointStatus::DISABLED,
                'disabled_reason' => $reason,
                'disabled_at'     => now(),
            ])->save();

            $endpoint->deliveries()
                     ->where('status', DeliveryStatus::PENDING)
                     ->update([
                         'status'          => DeliveryStatus::CANCELLED,
                         'next_attempt_at' => null,
                         'updated_at'      => now(),
                     ]);
        });

        return $endpoint;
    }
}

<?php

namespace ChiefTools\SDK\Webhook\Outbound\Http\Resources;

use Illuminate\Http\Request;
use Dedoc\Scramble\Attributes\SchemaName;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \ChiefTools\SDK\Webhook\Outbound\Models\Endpoint
 */
#[SchemaName('WebhookEndpoint')]
class EndpointResource extends JsonResource
{
    /** Shows the signing secret, only right after it was created or rotated. */
    public bool $withSecret = false;

    public function withSecret(): static
    {
        $this->withSecret = true;

        return $this;
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            /**
             * Formatted endpoint ID.
             *
             * @example webhook_01m6v2nkcfd8n7hg5r9t5k0ys6
             */
            'id' => $this->formatted_id,
            /**
             * HTTPS URL that receives the events.
             *
             * @example https://example.com/webhooks/chief
             */
            'url' => $this->url,
            /**
             * What the endpoint is for.
             *
             * @example Paging
             */
            'description' => $this->description,
            /**
             * Event types the endpoint receives, `*` for every event including types added later.
             *
             * @var list<\ChiefTools\SDK\Webhook\Outbound\Contracts\WebhookEventType|'*'>
             *
             * @example ["server.offline", "server.online"]
             */
            'events' => $this->events,
            /** Whether deliveries are sent. A disabled endpoint receives nothing until it is enabled again. */
            'status' => $this->status,
            /** Why the endpoint was disabled, null while it is enabled. */
            'disabled_reason' => $this->disabled_reason,
            /** ISO 8601 time the endpoint was disabled, null while it is enabled. */
            'disabled_at' => $this->disabled_at?->toIso8601String(),
            /** ISO 8601 time the endpoint started failing every delivery, null while deliveries succeed. It is disabled after five days. */
            'failing_since' => $this->failing_since?->toIso8601String(),
            /**
             * Signing secret, only set in the response that creates the endpoint or rotates its secret, null otherwise. Store it with your other secrets.
             *
             * @example whsec_MfKQ9r8GKYqrTwjUPD8ILPZIo2LaLaSw
             */
            'secret' => $this->withSecret ? $this->secret : null,
            /** ISO 8601 time until which the secret from before the last rotation still signs deliveries next to the current one, null when only one secret signs. */
            'previous_secret_expires_at' => $this->hasActivePreviousSecret() ? $this->previous_secret_expires_at?->toIso8601String() : null,
            /** ISO 8601 creation timestamp. */
            'created_at' => $this->created_at->toIso8601String(),
            /** ISO 8601 timestamp of the last change. */
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

<?php

namespace ChiefTools\SDK\Webhook\Outbound\Http\Resources;

use Illuminate\Http\Request;
use Dedoc\Scramble\Attributes\SchemaName;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \ChiefTools\SDK\Webhook\Outbound\Models\Event
 */
#[SchemaName('WebhookEvent')]
class EventResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            /**
             * Formatted event ID, the same as `id` in the payload and the `webhook-id` header.
             *
             * @example event_01m6v3f5aydrktmzrbpz1pqh7e
             */
            'id' => (string)$this->formatted_id,
            /**
             * Event type.
             *
             * @var \ChiefTools\SDK\Webhook\Outbound\Contracts\WebhookEventType
             */
            'type' => $this->type,
            /**
             * Version of the payload format.
             *
             * @example v1
             */
            'api_version' => $this->api_version,
            /** ISO 8601 time the event happened. */
            'created_at' => $this->created_at->toIso8601String(),
            /**
             * The body every delivery of the event sends, see the event's schema for its fields.
             *
             * @var array<string, mixed>
             */
            'payload' => (object)$this->decodedPayload(),
            /** The event's deliveries, one per endpoint. Only returned for a single event. */
            'deliveries' => DeliveryResource::collection($this->whenLoaded('deliveries')),
        ];
    }
}

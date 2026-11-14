<?php

namespace ChiefTools\SDK\Webhook\Outbound\Http\Resources;

use Illuminate\Http\Request;
use Dedoc\Scramble\Attributes\SchemaName;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \ChiefTools\SDK\Webhook\Outbound\Models\Delivery
 */
#[SchemaName('WebhookDelivery')]
class DeliveryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            /**
             * Formatted delivery ID.
             *
             * @example delivery_01m6v3f5b0c8z6w4k2t9r7p5n3
             */
            'id' => (string)$this->formatted_id,
            /**
             * ID of the endpoint it is sent to.
             *
             * @example webhook_01m6v2nkcfd8n7hg5r9t5k0ys6
             */
            'endpoint_id' => (string)$this->endpoint->formatted_id,
            /**
             * ID of the event it sends.
             *
             * @example event_01m6v3f5aydrktmzrbpz1pqh7e
             */
            'event_id' => (string)$this->event->formatted_id,
            /**
             * Type of the event it sends.
             *
             * @var \ChiefTools\SDK\Webhook\Outbound\Contracts\WebhookEventType
             */
            'event_type' => $this->event->type,
            'status'     => $this->status,
            /** Attempts made so far, resends included. */
            'attempts' => $this->attempts,
            /** ISO 8601 time the next automatic attempt is due, null when none is scheduled. */
            'next_attempt_at' => $this->isPending() ? $this->next_attempt_at?->toIso8601String() : null,
            /** ISO 8601 time of the last attempt, null before the first. */
            'last_attempted_at' => $this->last_attempted_at?->toIso8601String(),
            /** HTTP status of the last response, null before the first attempt or when no response came. */
            'last_response_status' => $this->last_response_status,
            /** ISO 8601 creation timestamp. */
            'created_at' => $this->created_at->toIso8601String(),
            /** Every attempt with its request and response, oldest first. Only returned for a single delivery. */
            'attempt_log' => AttemptResource::collection($this->whenLoaded('attemptLog')),
        ];
    }
}

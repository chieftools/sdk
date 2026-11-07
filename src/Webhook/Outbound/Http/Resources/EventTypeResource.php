<?php

namespace ChiefTools\SDK\Webhook\Outbound\Http\Resources;

use Illuminate\Http\Request;
use Dedoc\Scramble\Attributes\SchemaName;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \ChiefTools\SDK\Webhook\Outbound\Contracts\WebhookEventType $resource
 */
#[SchemaName('WebhookEventTypeDetails')]
class EventTypeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            /**
             * Event type, as `type` in the payload and in an endpoint's `events`.
             *
             * @var \ChiefTools\SDK\Webhook\Outbound\Contracts\WebhookEventType
             */
            'type' => $this->resource,
            /**
             * Heading the event is listed under in the dashboard.
             *
             * @example Availability
             */
            'group' => $this->resource->group(),
            /**
             * When the event is sent.
             *
             * @example A server stopped checking in.
             */
            'description' => $this->resource->description(),
        ];
    }
}

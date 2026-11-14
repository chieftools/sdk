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
            'group' => $this->eventGroup(),
            /**
             * When the event is sent.
             *
             * @example A server stopped checking in.
             */
            'description' => $this->eventDescription(),
        ];
    }

    /** The heading of the event type, typed here because the documentation does not follow calls on the interface. */
    private function eventGroup(): string
    {
        return $this->resource->group();
    }

    private function eventDescription(): string
    {
        return $this->resource->description();
    }
}

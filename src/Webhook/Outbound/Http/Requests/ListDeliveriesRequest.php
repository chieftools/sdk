<?php

namespace ChiefTools\SDK\Webhook\Outbound\Http\Requests;

use Illuminate\Validation\Rule;
use ChiefTools\SDK\Webhook\Outbound\Enums\DeliveryStatus;

class ListDeliveriesRequest extends ListEventsRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            /** Only deliveries in this state. */
            'status' => ['sometimes', Rule::enum(DeliveryStatus::class)],
        ];
    }
}

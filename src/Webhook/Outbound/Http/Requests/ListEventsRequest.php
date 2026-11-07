<?php

namespace ChiefTools\SDK\Webhook\Outbound\Http\Requests;

use Illuminate\Validation\Rule;
use ChiefTools\SDK\Webhook\Outbound\Webhooks;
use ChiefTools\SDK\Webhook\Outbound\Enums\BuiltInEvent;

class ListEventsRequest extends ListRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            /**
             * Only events of this type.
             *
             * @example server.offline
             */
            'type' => ['sometimes', 'string', Rule::in(self::eventTypes())],
        ];
    }

    /** @return list<string> */
    public static function eventTypes(): array
    {
        return [
            ...array_map(static fn ($type): string => $type->value, app(Webhooks::class)->eventTypes()),
            ...array_map(static fn (BuiltInEvent $type): string => $type->value, BuiltInEvent::cases()),
        ];
    }
}

<?php

namespace ChiefTools\SDK\Webhook\Outbound\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use ChiefTools\SDK\Webhook\Outbound\Actions\EndpointInput;

class UpdateWebhookEndpointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        // Fields left out keep their value, `UpdateEndpoint` validates the result again with `EndpointInput`
        return [
            /**
             * HTTPS URL that receives the events, it must resolve to a public address.
             *
             * @example https://example.com/webhooks/chief
             */
            'url' => ['sometimes', ...EndpointInput::urlRules()],
            /**
             * What the endpoint is for, shown in the dashboard. Null removes it.
             *
             * @example Paging
             */
            'description' => ['sometimes', 'nullable', 'string', 'max:' . EndpointInput::MAX_DESCRIPTION_LENGTH],
            /**
             * Event types the endpoint receives, `*` for every event including types added later. Replaces the current list.
             *
             * @example ["*"]
             */
            'events'   => ['sometimes', 'array', 'min:1'],
            'events.*' => ['required', 'string', 'distinct', Rule::in(EndpointInput::allowedEvents())],
        ];
    }
}

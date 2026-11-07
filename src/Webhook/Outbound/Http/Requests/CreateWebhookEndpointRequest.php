<?php

namespace ChiefTools\SDK\Webhook\Outbound\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use ChiefTools\SDK\Webhook\Outbound\Actions\EndpointInput;

class CreateWebhookEndpointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        // Documents the input, `CreateEndpoint` validates it again with `EndpointInput`
        return [
            /**
             * HTTPS URL that receives the events, it must resolve to a public address.
             *
             * @example https://example.com/webhooks/chief
             */
            'url' => EndpointInput::urlRules(),
            /**
             * What the endpoint is for, shown in the dashboard.
             *
             * @example Paging
             */
            'description' => ['nullable', 'string', 'max:' . EndpointInput::MAX_DESCRIPTION_LENGTH],
            /**
             * Event types the endpoint receives, `*` for every event including types added later.
             *
             * @example ["server.offline", "server.online"]
             */
            'events'   => ['required', 'array', 'min:1'],
            'events.*' => ['required', 'string', 'distinct', Rule::in(EndpointInput::allowedEvents())],
        ];
    }
}

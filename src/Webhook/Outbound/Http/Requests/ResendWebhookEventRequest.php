<?php

namespace ChiefTools\SDK\Webhook\Outbound\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResendWebhookEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            /**
             * ID of the endpoint to send the event to. It gets the event even when it did not receive it before, or does not subscribe to its type.
             *
             * @example webhook_01m6v2nkcfd8n7hg5r9t5k0ys6
             */
            'endpoint' => ['required', 'string'],
        ];
    }
}

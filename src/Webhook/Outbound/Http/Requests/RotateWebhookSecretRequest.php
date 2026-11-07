<?php

namespace ChiefTools\SDK\Webhook\Outbound\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RotateWebhookSecretRequest extends FormRequest
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
             * Rotate even though the secret from the last rotation still signs deliveries. That secret stops signing right
             * away, receivers that only know it start rejecting deliveries.
             *
             * @default false
             */
            'replace_previous' => ['sometimes', 'boolean'],
        ];
    }
}

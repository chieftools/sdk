<?php

namespace ChiefTools\SDK\Webhook\Outbound\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListRequest extends FormRequest
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
             * Number of results per page.
             *
             * @default 50
             */
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            /**
             * Page number to return.
             *
             * @default 1
             */
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function perPage(): int
    {
        return $this->integer('per_page', 50);
    }
}

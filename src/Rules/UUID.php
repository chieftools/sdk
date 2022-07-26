<?php

namespace ChiefTools\SDK\Rules;

use Illuminate\Contracts\Validation\Rule;

class UUID implements Rule
{
    public function passes($attribute, $value): bool
    {
        return preg_match('/([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})/', $value);
    }

    public function message(): string
    {
        return 'The :attribute must be a valid UUID.';
    }
}

<?php

namespace IronGate\Chief\Rules;

use Illuminate\Contracts\Validation\Rule;

class UUID implements Rule
{
    public function passes($attribute, $value)
    {
        return preg_match('/([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})/', $value);
    }

    public function message()
    {
        return 'The :attribute must be a valid UUID.';
    }
}

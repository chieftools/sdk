<?php

namespace IronGate\Chief\Http\Requests\API;

use IronGate\Chief\Http\Requests\Request;

class CreateToken extends Request
{
    public function rules(): array
    {
        return [
            'name' => 'required',
        ];
    }
}

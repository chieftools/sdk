<?php

namespace IronGate\Integration\Http\Requests\API;

use IronGate\Integration\Http\Requests\Request;

class CreateToken extends Request
{
    public function rules(): array
    {
        return [
            'name' => 'required',
        ];
    }
}

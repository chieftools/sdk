<?php

namespace IronGate\Chief\Entities\Passport;

use Laravel\Passport\Token as PassportToken;

class Token extends PassportToken
{
    protected $dates = [
        'expires_at',
        'created_at',
        'updated_at',
    ];
}

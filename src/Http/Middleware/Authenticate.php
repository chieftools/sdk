<?php

namespace IronGate\Chief\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as IlluminateAuthenticate;

class Authenticate extends IlluminateAuthenticate
{
    protected function authenticate($request, array $guards): void
    {
        parent::authenticate($request, $guards);

        sync_user_timezone();
    }
}

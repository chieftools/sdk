<?php

namespace ChiefTools\SDK\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Auth\Middleware\Authenticate as IlluminateAuthenticate;

class Authenticate extends IlluminateAuthenticate
{
    protected function authenticate($request, array $guards): void
    {
        parent::authenticate($request, $guards);

        sync_user_timezone();
    }

    protected function redirectTo(Request $request): ?string
    {
        $redirectPath = parent::redirectTo($request);

        if ($redirectPath !== null) {
            return $redirectPath;
        }

        if (Route::has('auth.login')) {
            return route('auth.login');
        }

        if (Route::has('login')) {
            return route('login');
        }

        return null;
    }
}

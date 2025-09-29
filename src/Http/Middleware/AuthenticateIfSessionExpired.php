<?php

namespace ChiefTools\SDK\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class AuthenticateIfSessionExpired
{
    public function handle(Request $request, callable $next): mixed
    {
        if ($request->user() === null && $request->cookie(config('chief.id') . '_auth') === '1') {
            // Expire the cookie when we "use" it, so we don't get stuck in a redirect loop
            Cookie::expire(config('chief.id') . '_auth');

            return to_route('auth.login');
        }

        return $next($request);
    }
}

<?php

namespace IronGate\Integration\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * This middleware moves a OAuth access token from the `access_token`
 * URL parameter to the `Authorization` header for authentication.
 * This shoud _not_ be used if it can be prevented. ¯\_(ツ)_/¯.
 */
class MoveAccessTokenFromURLToHeader
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->filled('access_token') && empty($request->header('Authorization'))) {
            $request->headers->add([
                'Authorization' => 'Bearer ' . $request->input('access_token'),
            ]);
        }

        return $next($request);
    }
}

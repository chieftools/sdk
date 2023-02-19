<?php

namespace ChiefTools\SDK\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * This middleware moves a bearer access token from specified input key (defaults to `access_token`)
 * to the `Authorization` header as a bearer token that can be consumed by regular auth guards.
 *
 * This shoud _not_ be used if it can be prevented, but that ain't always an option ¯\_(ツ)_/¯
 */
class MoveAccessTokenFromURLToHeader
{
    public function handle(Request $request, Closure $next, string $key = 'access_token'): mixed
    {
        $token = $request->filled($key) ? $request->input($key) : null;

        if ($token !== null && empty($request->header('Authorization'))) {
            $request->headers->set('Authorization', "Bearer {$token}");
        }

        return $next($request);
    }

    public static function withKey(string $key): string
    {
        return self::class . ":{$key}";
    }
}

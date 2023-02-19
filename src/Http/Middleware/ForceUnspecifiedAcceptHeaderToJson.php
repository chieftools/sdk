<?php

namespace ChiefTools\SDK\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceUnspecifiedAcceptHeaderToJson
{
    public function handle(Request $request, Closure $next): mixed
    {
        $acceptHeader = $request->headers->get('Accept');

        if ($acceptHeader === null || $acceptHeader === '*' || $acceptHeader === '*/*') {
            $request->headers->set('Accept', 'application/json');
        }

        return $next($request);
    }
}

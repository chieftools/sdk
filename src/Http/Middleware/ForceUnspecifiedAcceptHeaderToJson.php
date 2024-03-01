<?php

namespace ChiefTools\SDK\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceUnspecifiedAcceptHeaderToJson
{
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->acceptsAnyContentType()) {
            $request->headers->set('Accept', 'application/json');
        }

        return $next($request);
    }
}

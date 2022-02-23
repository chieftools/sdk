<?php

namespace IronGate\Chief\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceSecure
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (!$request->secure()) {
            return redirect()->secure($request->path());
        }

        return $next($request);
    }
}

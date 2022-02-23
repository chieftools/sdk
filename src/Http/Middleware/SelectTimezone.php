<?php

namespace IronGate\Chief\Http\Middleware;

use Closure;

class SelectTimezone
{
    public function handle($request, Closure $next): mixed
    {
        sync_user_timezone();

        return $next($request);
    }
}

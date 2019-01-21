<?php

namespace IronGate\Integration\Http\Middleware;

use Closure;
use RuntimeException;

class AuthenticateChief
{
    public function handle($request, Closure $next)
    {
        if (empty(config('services.chief.webhook_secret'))) {
            throw new RuntimeException('Missing a Chief webhook secret, not accepting any webhook calls!');
        }

        abort_unless($request->header('X-Chief-Secret') === config('services.chief.webhook_secret'), 401);

        return $next($request);
    }
}

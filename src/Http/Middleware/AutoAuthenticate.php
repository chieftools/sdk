<?php

namespace IronGate\Chief\Http\Middleware;

use Closure;
use RuntimeException;
use Illuminate\Auth\Middleware\Authenticate;

class AutoAuthenticate extends Authenticate
{
    public function handle($request, Closure $next, ...$guards)
    {
        if (!empty($guards)) {
            throw new RuntimeException('Auto authenticator does not take guards parameter.');
        }

        $this->authenticate($request, ['api', 'web']);

        sync_user_timezone();

        return $next($request);
    }
}

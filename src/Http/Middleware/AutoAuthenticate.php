<?php

namespace ChiefTools\SDK\Http\Middleware;

use Closure;
use RuntimeException;

class AutoAuthenticate extends Authenticate
{
    public function handle($request, Closure $next, ...$guards): mixed
    {
        if (!empty($guards)) {
            throw new RuntimeException('Auto authenticator does not take guards parameter.');
        }

        $this->authenticate($request, ['api', 'web']);

        return $next($request);
    }
}

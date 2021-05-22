<?php

namespace IronGate\Chief\GraphQL\Middleware;

use Nuwave\Lighthouse\Support\Http\Middleware\AttemptAuthentication;

class AutoAuthenticate extends AttemptAuthentication
{
    protected function attemptAuthentication(array $guards): void
    {
        $guards = empty($guards) ? ['api', 'web'] : $guards;

        foreach ($guards as $guard) {
            if ($this->authFactory->guard($guard)->check()) {
                config(['lighthouse.guard' => $guard]);

                $this->authFactory->shouldUse($guard);

                return;
            }
        }
    }
}

<?php

namespace ChiefTools\SDK\GraphQL\Middleware;

use RuntimeException;
use Nuwave\Lighthouse\Http\Middleware\AttemptAuthentication;

class AuthenticateWeb extends AttemptAuthentication
{
    protected function attemptAuthentication(array $guards): void
    {
        if (!empty($guards)) {
            throw new RuntimeException('GraphQL authenticator does not take guards parameter.');
        }

        config(['lighthouse.guard' => 'web']);

        $this->authFactory->shouldUse('web');
    }
}

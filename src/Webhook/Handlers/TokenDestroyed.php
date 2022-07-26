<?php

namespace ChiefTools\SDK\Webhook\Handlers;

use ChiefTools\SDK\Helpers\RandomToken;
use ChiefTools\SDK\Exceptions\RandomToken\InvalidTokenException;

class TokenDestroyed extends BaseHandler
{
    public function __invoke(array $payload): ?array
    {
        try {
            $token = RandomToken::fromString(array_get($payload, 'data.token'));

            cache()->forget($token->cacheKey());
        } catch (InvalidTokenException) {
            return null;
        }

        return null;
    }
}

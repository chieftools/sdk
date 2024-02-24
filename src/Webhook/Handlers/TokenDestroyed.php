<?php

namespace ChiefTools\SDK\Webhook\Handlers;

use Stayallive\RandomTokens\RandomToken;
use Stayallive\RandomTokens\Exceptions\InvalidTokenException;

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

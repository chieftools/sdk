<?php

namespace ChiefTools\SDK\Webhook\Handlers;

class TokenDestroyed extends BaseHandler
{
    public function __invoke(array $payload): ?array
    {
        cache()->forget(array_get($payload, 'data.cache_key'));

        return null;
    }
}

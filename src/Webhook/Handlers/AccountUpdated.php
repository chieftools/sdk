<?php

namespace ChiefTools\SDK\Webhook\Handlers;

use ChiefTools\SDK\Socialite\ChiefUser;

class AccountUpdated extends BaseHandler
{
    public function __invoke(array $payload): ?array
    {
        $user = $this->getUserFromPayload($payload);

        if ($user === null) {
            return null;
        }

        $user->updateFromRemote(
            new ChiefUser(array_get($payload, 'data')),
        );

        return null;
    }
}

<?php

namespace ChiefTools\SDK\Webhook\Handlers;

class TeamDestroyed extends BaseHandler
{
    public function __invoke(array $payload): ?array
    {
        $this->getTeamFromPayload($payload)?->delete();

        return null;
    }
}

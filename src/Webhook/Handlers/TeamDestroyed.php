<?php

namespace IronGate\Chief\Webhook\Handlers;

class TeamDestroyed extends BaseHandler
{
    public function __invoke(array $payload): ?array
    {
        $this->getTeamFromPayload($payload)?->delete();

        return null;
    }
}

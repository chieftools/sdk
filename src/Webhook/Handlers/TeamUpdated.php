<?php

namespace IronGate\Chief\Webhook\Handlers;

use IronGate\Chief\Socialite\ChiefTeam;

class TeamUpdated extends BaseHandler
{
    public function __invoke(array $payload): ?array
    {
        $team = $this->getTeamFromPayload($payload);

        if ($team === null) {
            return null;
        }

        $team->updateFromRemote(
            ChiefTeam::fromArray(array_get($payload, 'data'))
        );

        return null;
    }
}

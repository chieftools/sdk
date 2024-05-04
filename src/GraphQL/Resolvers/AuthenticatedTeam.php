<?php

namespace ChiefTools\SDK\GraphQL\Resolvers;

use ChiefTools\SDK\Entities\Team;
use ChiefTools\SDK\Entities\User;

class AuthenticatedTeam extends AuthenticatedEntityResolver
{
    protected function execute(): ?Team
    {
        return $this->resolveAuthenticatedEntity(Team::class)
               ?? $this->resolveFromAuthenticatedUser();
    }

    private function resolveFromAuthenticatedUser(): ?Team
    {
        $user = $this->resolveAuthenticatedEntity(User::class);

        if ($user === null) {
            return null;
        }

        $team = $this->filled('hint')
            ? $user->teams()->where('slug', '=', $this->input('hint'))->first()
            : $user->currentTeam();

        $user->setCurrentTeam($team);

        return $team;
    }
}

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
            ? $this->resolveHintedTeam($user)
            : $user->currentTeam();

        if ($team === null) {
            return null;
        }

        $user->setCurrentTeam($team);

        return $team;
    }

    /** A token limited to one team never resolves another team, even one the user is a member of. */
    private function resolveHintedTeam(User $user): ?Team
    {
        $team = $user->teams()->where('slug', '=', $this->input('hint'))->first();

        $tokenTeamId = $user->getChiefRemoteAccessToken()?->teamId;

        if ($team === null || ($tokenTeamId !== null && $team->id !== $tokenTeamId)) {
            return null;
        }

        return $team;
    }
}

<?php

namespace ChiefTools\SDK\GraphQL\Resolvers;

use ChiefTools\SDK\Entities\Team;
use ChiefTools\SDK\Entities\User;
use ChiefTools\SDK\GraphQL\Exceptions\MissingScopeException;

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

        $this->ensureTokenCanResolveTeam($user);

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
        return $user->accessibleTeams()->where('slug', '=', $this->input('hint'))->first();
    }

    /** A user token needs the `teams` scope or any scope of this app, a token with only profile scopes has no use for a team. */
    private function ensureTokenCanResolveTeam(User $user): void
    {
        $token = $user->getChiefRemoteAccessToken();
        $appId = config('chief.id');

        if ($token === null || $token->can('teams') || ($appId !== null && $token->hasAnyScopeFor($appId))) {
            return;
        }

        throw new MissingScopeException('team', array_values(array_filter(['teams', $appId])), $token->scopes, allScopesRequired: false);
    }
}

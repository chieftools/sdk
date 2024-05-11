<?php

namespace ChiefTools\SDK\Auth;

use Exception;
use ChiefTools\SDK\Chief;
use ChiefTools\SDK\Entities\Team;
use ChiefTools\SDK\Entities\User;
use ChiefTools\SDK\Enums\TokenPrefix;
use Stayallive\RandomTokens\RandomToken;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Contracts\Auth\Authenticatable;

readonly class RemoteUserAccessTokenGuard extends RemoteAccessTokenGuard
{
    protected function isSupportedToken(RandomToken $token): bool
    {
        return TokenPrefix::PERSONAL_ACCESS_TOKEN->isForToken($token) || TokenPrefix::OAUTH_ACCESS_TOKEN->isForToken($token);
    }

    protected function resolveAuthenticatableForRemoteToken(ChiefRemoteAccessToken $remoteAccessToken): ?Authenticatable
    {
        $user = $this->resolveUserForRemoteToken($remoteAccessToken);

        if ($user === null) {
            return null;
        }

        if ($remoteAccessToken->teamId !== null) {
            retry(2, function () use (&$user, $remoteAccessToken) {
                $user->setCurrentTeam(
                    $user->teams->firstOrFail(
                        static fn (Team $team) => $team->id === $remoteAccessToken->teamId,
                    ),
                );
            }, when: function (Exception $e) use (&$user, $remoteAccessToken) {
                if ($e instanceof ItemNotFoundException) {
                    // If we can't find the team update the user from the mothership to sync their teams and retry the lookup
                    $user = $this->resolveUserFromMothershipForRemoteToken($remoteAccessToken);

                    return true;
                }

                return false;
            });
        }

        return $user;
    }

    private function resolveUserForRemoteToken(ChiefRemoteAccessToken $remoteToken): ?User
    {
        if ($remoteToken->userId === null) {
            return null;
        }

        return $this->resolveUserFromDatabaseForRemoteToken($remoteToken)
               ?? $this->resolveUserFromMothershipForRemoteToken($remoteToken);
    }

    private function resolveUserFromDatabaseForRemoteToken(ChiefRemoteAccessToken $remoteToken): ?User
    {
        if ($remoteToken->userId === null) {
            return null;
        }

        return Chief::userModel()::query()->where('chief_id', '=', $remoteToken->userId)->first();
    }

    private function resolveUserFromMothershipForRemoteToken(ChiefRemoteAccessToken $remoteToken): ?User
    {
        if ($remoteToken->userId === null) {
            return null;
        }

        try {
            $remote = $this->client->user($remoteToken->userId, [
                'team_hint'     => $remoteToken->teamId,
                'mark_as_usage' => '1',
            ]);

            if ($remote !== null) {
                return Chief::userModel()::createOrUpdateFromRemote($remote);
            }
        } catch (Exception $e) {
            report($e);
        }

        return null;
    }
}

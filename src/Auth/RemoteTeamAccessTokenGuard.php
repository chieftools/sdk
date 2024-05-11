<?php

namespace ChiefTools\SDK\Auth;

use ChiefTools\SDK\Chief;
use ChiefTools\SDK\Enums\TokenPrefix;
use Stayallive\RandomTokens\RandomToken;
use Illuminate\Contracts\Auth\Authenticatable;

readonly class RemoteTeamAccessTokenGuard extends RemoteAccessTokenGuard
{
    protected function isSupportedToken(RandomToken $token): bool
    {
        return TokenPrefix::TEAM_ACCESS_TOKEN->isForToken($token);
    }

    protected function resolveAuthenticatableForRemoteToken(ChiefRemoteAccessToken $remoteAccessToken): ?Authenticatable
    {
        if ($remoteAccessToken->teamId === null) {
            return null;
        }

        return Chief::teamModel()::query()->where('id', '=', $remoteAccessToken->teamId)->first();
    }
}

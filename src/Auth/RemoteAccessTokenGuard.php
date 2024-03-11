<?php

namespace ChiefTools\SDK\Auth;

use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use ChiefTools\SDK\API\Client;
use ChiefTools\SDK\Entities\Team;
use ChiefTools\SDK\Entities\User;
use Illuminate\Cache\CacheManager;
use ChiefTools\SDK\Enums\TokenPrefix;
use Stayallive\RandomTokens\RandomToken;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Support\ItemNotFoundException;
use Stayallive\RandomTokens\Exceptions\InvalidTokenException;

readonly class RemoteAccessTokenGuard
{
    public function __construct(
        private string $guard,
        private Client $client,
        private CacheManager $cache,
    ) {}

    public function __invoke(Request $request): ?User
    {
        $tokenFromRequest = $this->getTokenFromRequest($request);

        // Make sure the request has a token
        if (empty($tokenFromRequest)) {
            return null;
        }

        // Parse the token from the request
        try {
            $randomToken = RandomToken::fromString($tokenFromRequest);
        } catch (InvalidTokenException) {
            return null;
        }

        // Make sure it's a personal access token or an OAuth access token
        if (!TokenPrefix::PERSONAL_ACCESS_TOKEN->isForToken($randomToken) && !TokenPrefix::OAUTH_ACCESS_TOKEN->isForToken($randomToken)) {
            return null;
        }

        $response = $this->cache->get($randomToken->cacheKey());

        if ($response === null) {
            try {
                $response = $this->client->validateAccessToken((string)$randomToken);
                $timeout  = 60 * 60; // 1 hour (result might change)
            } catch (ClientException) {
                $response = false;
                $timeout  = 60 * 60 * 6; // 6 hours (result will not change)
            }

            $this->cache->put($randomToken->cacheKey(), $response ?? false, $timeout);
        }

        if (empty($response)) {
            return null;
        }

        $expires = empty($response['expires_at']) ? false : Carbon::createFromTimestamp($response['expires_at']);

        if ($expires instanceof Carbon && $expires->isPast()) {
            return null;
        }

        $remoteToken = new ChiefRemoteAccessToken(
            scopes: $response['scopes'],
            userId: $response['user_id'],
            teamId: $response['team_id'] ?? null,
            expiresAt: $expires ?: null,
        );

        $user = $this->resolveUserForRemoteToken($remoteToken);

        if ($user !== null) {
            if (in_array(HasRemoteTokens::class, class_uses_recursive(config('chief.auth.model')), true)) {
                $user = $user->withChiefRemoteAccessToken($remoteToken);
            }

            if ($remoteToken->teamId !== null) {
                retry(2, function () use (&$user, $remoteToken) {
                    $user->setCurrentTeam(
                        $user->teams->firstOrFail(
                            static fn (Team $team) => $team->id === $remoteToken->teamId,
                        ),
                    );
                }, when: function (Exception $e) use (&$user, $remoteToken) {
                    if ($e instanceof ItemNotFoundException) {
                        // If we can't find the team update the user from the mothership to sync their teams
                        $user = $this->resolveUserFromMothershipForRemoteToken($remoteToken);

                        return true;
                    }

                    return false;
                });
            }

            event(new Authenticated($this->guard, $user));
        }

        return $user;
    }

    protected function getTokenFromRequest(Request $request): ?string
    {
        return $request->bearerToken();
    }

    private function resolveUserForRemoteToken(ChiefRemoteAccessToken $remoteToken): ?User
    {
        return $this->resolveUserFromDatabaseForRemoteToken($remoteToken)
               ?? $this->resolveUserFromMothershipForRemoteToken($remoteToken);
    }

    private function resolveUserFromDatabaseForRemoteToken(ChiefRemoteAccessToken $remoteToken): ?User
    {
        return config('chief.auth.model')::query()->where('chief_id', '=', $remoteToken->userId)->first();
    }

    private function resolveUserFromMothershipForRemoteToken(ChiefRemoteAccessToken $remoteAccessToken): ?User
    {
        try {
            $remote = $this->client->user($remoteAccessToken->userId, [
                'team_hint'     => $remoteAccessToken->teamId,
                'mark_as_usage' => '1',
            ]);

            if ($remote !== null) {
                /** @var \ChiefTools\SDK\Entities\User $user */
                $user = config('chief.auth.model')::createOrUpdateFromRemote($remote);

                return $user;
            }
        } catch (Exception $e) {
            report($e);
        }

        return null;
    }
}

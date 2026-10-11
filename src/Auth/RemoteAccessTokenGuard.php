<?php

namespace ChiefTools\SDK\Auth;

use Carbon\Carbon;
use Illuminate\Http\Request;
use ChiefTools\SDK\API\Client;
use ChiefTools\SDK\Entities\Team;
use ChiefTools\SDK\Entities\User;
use Illuminate\Cache\CacheManager;
use Illuminate\Database\Eloquent\Model;
use Stayallive\RandomTokens\RandomToken;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Auth\Events\Authenticated;
use ChiefTools\SDK\Enums\Team\MembershipRole;
use Illuminate\Contracts\Auth\Authenticatable;
use Stayallive\RandomTokens\Exceptions\InvalidTokenException;

abstract readonly class RemoteAccessTokenGuard
{
    public function __construct(
        protected string $guard,
        protected Client $client,
        protected CacheManager $cache,
    ) {}

    abstract protected function isSupportedToken(RandomToken $token): bool;

    abstract protected function resolveAuthenticatableForRemoteToken(ChiefRemoteAccessToken $remoteAccessToken): ?Authenticatable;

    public function __invoke(Request $request): ?Authenticatable
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

        if (!$this->isSupportedToken($randomToken)) {
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

        $expires = empty($response['expires_at'])
            ? false
            : Carbon::createFromTimestamp($response['expires_at']);

        if ($expires instanceof Carbon && $expires->isPast()) {
            return null;
        }

        $remoteToken = new ChiefRemoteAccessToken(
            id: $response['id'] ?? 'unknown',
            name: $response['name'] ?? 'unnamed token',
            prefix: $randomToken->prefix,
            scopes: $response['scopes'],
            userId: $response['user_id'] ?? null,
            teamId: $response['team_id'] ?? null,
            teamRole: isset($response['team_role']) ? MembershipRole::tryFrom($response['team_role']) : null,
            audience: $response['audience'] ?? null,
            expiresAt: $expires ?: null,
        );

        $authenticatable = $this->resolveAuthenticatableForRemoteToken($remoteToken);

        if ($authenticatable === null) {
            return null;
        }

        $this->ensureTeamHeaderMatchesBoundTeam($request, $remoteToken, $authenticatable);

        if ($authenticatable instanceof Model) {
            $authenticatable->preventsLazyLoading = false;
        }

        if ($authenticatable instanceof AuthenticatesWithRemoteToken) {
            $authenticatable = $authenticatable->withChiefRemoteAccessToken($remoteToken);
        }

        event(new Authenticated($this->guard, $authenticatable));

        return $authenticatable;
    }

    /**
     * A team header must name the team the token is bound to, an empty header counts as no header.
     *
     * Tokens not bound to a team validate the header when the current team is resolved.
     */
    private function ensureTeamHeaderMatchesBoundTeam(Request $request, ChiefRemoteAccessToken $remoteToken, Authenticatable $authenticatable): void
    {
        $teamFromHeader = trim((string)$request->header('x-chief-team'));

        if ($teamFromHeader === '') {
            return;
        }

        $boundTeam = match (true) {
            $authenticatable instanceof Team                                  => $authenticatable,
            $authenticatable instanceof User && $remoteToken->teamId !== null => $authenticatable->getMemoizedCurrentTeam(),
            default                                                           => null,
        };

        abort_if($boundTeam !== null && $boundTeam->slug !== $teamFromHeader, 404, 'The team from the X-Chief-Team header does not match the team this token is limited to.');
    }

    protected function getTokenFromRequest(Request $request): ?string
    {
        return $request->bearerToken();
    }
}

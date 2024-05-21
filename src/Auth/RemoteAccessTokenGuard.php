<?php

namespace ChiefTools\SDK\Auth;

use Carbon\Carbon;
use Illuminate\Http\Request;
use ChiefTools\SDK\API\Client;
use Illuminate\Cache\CacheManager;
use Illuminate\Database\Eloquent\Model;
use Stayallive\RandomTokens\RandomToken;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Auth\Events\Authenticated;
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

        $expires = empty($response['expires_at']) ? false : Carbon::createFromTimestamp($response['expires_at']);

        if ($expires instanceof Carbon && $expires->isPast()) {
            return null;
        }

        $remoteToken = new ChiefRemoteAccessToken(
            scopes: $response['scopes'],
            userId: $response['user_id'] ?? null,
            teamId: $response['team_id'] ?? null,
            expiresAt: $expires ?: null,
        );

        $authenticatable = $this->resolveAuthenticatableForRemoteToken($remoteToken);

        if ($authenticatable === null) {
            return null;
        }

        if ($authenticatable instanceof Model) {
            $authenticatable->preventsLazyLoading = false;
        }

        if (in_array(HasRemoteTokens::class, class_uses_recursive($authenticatable::class), true)) {
            /** @var \Illuminate\Contracts\Auth\Authenticatable&\ChiefTools\SDK\Auth\HasRemoteTokens $authenticatable */
            $authenticatable = $authenticatable->withChiefRemoteAccessToken($remoteToken);
        }

        event(new Authenticated($this->guard, $authenticatable));

        return $authenticatable;
    }

    protected function getTokenFromRequest(Request $request): ?string
    {
        return $request->bearerToken();
    }
}

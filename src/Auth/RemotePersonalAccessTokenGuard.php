<?php

namespace ChiefTools\SDK\Auth;

use Carbon\Carbon;
use Illuminate\Http\Request;
use ChiefTools\SDK\API\Client;
use ChiefTools\SDK\Entities\User;
use Illuminate\Cache\CacheManager;
use ChiefTools\SDK\Enums\TokenPrefix;
use ChiefTools\SDK\Helpers\RandomToken;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Auth\Events\Authenticated;
use ChiefTools\SDK\Exceptions\RandomToken\InvalidTokenException;

class RemotePersonalAccessTokenGuard
{
    public function __construct(
        private readonly string $guard,
        private readonly Client $client,
        private readonly CacheManager $cache,
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

        // Make sure it's a personal access token
        if (!TokenPrefix::PERSONAL_ACCESS_TOKEN->isForToken($randomToken)) {
            return null;
        }

        $response = $this->cache->get($randomToken->cacheKey());

        if ($response === null) {
            try {
                $response = $this->client->validatePAT((string)$randomToken);
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

        /** @var \ChiefTools\SDK\Entities\User|null $user */
        $user = User::query()->where('chief_id', '=', $response['user_id'])->first();

        if ($user !== null) {
            event(new Authenticated($this->guard, $user));
        }

        return $user;
    }

    protected function getTokenFromRequest(Request $request): ?string
    {
        return $request->bearerToken();
    }
}

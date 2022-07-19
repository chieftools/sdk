<?php

namespace IronGate\Chief\Auth;

use Carbon\Carbon;
use Illuminate\Http\Request;
use IronGate\Chief\API\Client;
use IronGate\Chief\Entities\User;
use Illuminate\Cache\CacheManager;
use IronGate\Chief\Enums\TokenPrefix;
use IronGate\Chief\Helpers\RandomToken;
use Illuminate\Auth\Events\Authenticated;
use IronGate\Chief\Exceptions\RandomToken\InvalidTokenException;

class RemotePersonalAccessTokenGuard
{
    public function __construct(
        private readonly string $guard,
        private readonly Client $client,
        private readonly CacheManager $cache,
    ) {
    }

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
            $response = $this->client->validatePAT((string)$randomToken);

            $this->cache->put($randomToken->cacheKey(), $response ?? false, 60 * 5);
        }

        if (empty($response)) {
            return null;
        }

        $expires = empty($response['expires_at']) ? false : Carbon::createFromTimestamp($response['expires_at']);

        if ($expires instanceof Carbon && $expires->isPast()) {
            return null;
        }

        /** @var \IronGate\Chief\Entities\User|null $user */
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

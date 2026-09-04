<?php

namespace ChiefTools\SDK\Http\Middleware;

use Closure;
use LogicException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use ChiefTools\SDK\Auth\AuthenticatesWithRemoteToken;

class EnsureTokenHasAudience
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next, string $audienceName): Response
    {
        $audience = config("chief.auth.audiences.{$audienceName}");

        if (!is_string($audience) || $audience === '') {
            throw new LogicException("Chief token audience [{$audienceName}] is not configured with a valid absolute URI.");
        }

        $components = parse_url($audience);

        if (!is_array($components) || !isset($components['scheme']) || array_key_exists('fragment', $components)) {
            throw new LogicException("Chief token audience [{$audienceName}] is not configured with a valid absolute URI.");
        }

        $user = $request->user();

        if (
            !$user instanceof AuthenticatesWithRemoteToken
            || !$user->hasChiefRemoteAccessToken()
            || !$user->getChiefRemoteAccessToken()?->isForAudience($audience)
        ) {
            return response('Token is not valid for this audience.', 401, [
                'WWW-Authenticate' => 'Bearer error="invalid_token"',
            ]);
        }

        return $next($request);
    }
}

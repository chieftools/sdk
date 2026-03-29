<?php

namespace ChiefTools\SDK\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use ChiefTools\SDK\Auth\AuthenticatesWithRemoteToken;

class EnsureTokenHasScope
{
    /**
     * Handle an incoming request.
     *
     * Checks if the authenticated user's token has at least one of the required scopes.
     * If the user is not authenticated via a remote token (e.g., session auth), access is granted.
     *
     * @param string ...$scopes One or more scopes to check (OR logic: any match grants access)
     */
    public function handle(Request $request, Closure $next, string ...$scopes): Response
    {
        $user = $request->user();

        // If not authenticated via token (e.g., session auth), allow access
        if (!$user instanceof AuthenticatesWithRemoteToken || !$user->hasChiefRemoteAccessToken()) {
            return $next($request);
        }

        $token = $user->getChiefRemoteAccessToken();

        foreach ($scopes as $scope) {
            if ($token->can($scope)) {
                return $next($request);
            }
        }

        abort(403, 'Insufficient token scope.');
    }
}

<?php

namespace ChiefTools\SDK\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TeamUrlContext
{
    public function handle(Request $request, Closure $next): mixed
    {
        /** @var \ChiefTools\SDK\Entities\User|null $user */
        $user = $request->user();
        $team = $user?->team;

        if ($team !== null) {
            $route = $request->route();

            $routeHint = $route?->originalParameter('team_hint');

            // If the route slug doesn't match, redirect to the route with the correct team slug
            if ($routeHint !== null && $team->slug !== $routeHint) {
                return redirect()->to(str_replace_first("/{$routeHint}/", "/{$team->slug}/", $request->fullUrl()));
            }

            $route?->forgetParameter('team_hint');

            $user->setCurrentTeam($team);
        }

        return $next($request);
    }
}

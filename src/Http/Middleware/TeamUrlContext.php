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
            $request->route()?->forgetParameter('team_hint');

            $user->setCurrentTeam($team);
        }

        return $next($request);
    }
}

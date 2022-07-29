<?php

namespace ChiefTools\SDK\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class TeamUrlContext
{
    public function handle(Request $request, Closure $next): mixed
    {
        /** @var \ChiefTools\SDK\Entities\Team|null $team */
        $team = $request->user()?->team;

        if ($team !== null) {
            URL::defaults(['team_hint' => $team->slug]);

            $request->route()?->forgetParameter('team_hint');

            $request->attributes->set('team_hint', $team);

            $team->maybeUpdateLastActivity();
        }

        return $next($request);
    }
}

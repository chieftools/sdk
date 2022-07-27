<?php

namespace ChiefTools\SDK\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class Team
{
    public function handle(Request $request, Closure $next): mixed
    {
        /** @var \ChiefTools\SDK\Entities\User|null $user */
        $user = $request->user();

        if ($user?->team !== null) {
            URL::defaults(['team' => $user->team->slug]);
        }

        return $next($request);
    }
}

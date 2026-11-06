<?php

namespace ChiefTools\SDK\Http\Controllers\Team;

use ChiefTools\SDK\Chief;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

/**
 * Opens the global team view, which combines all teams of the user, on the page the user was on when it allows it.
 */
class SwitchToGlobal
{
    public function __invoke(): RedirectResponse
    {
        $previousUrl = session()->previousUrl();

        if ($previousUrl !== null) {
            $route = Route::getRoutes()->match(Request::create($previousUrl));

            // A route with parameters next to the team hint shows something of one team, which the global team view might not show
            if (Chief::allowsGlobalTeam($route) && count($route->parameters()) === 1 && $route->parameter('team_hint') !== null) {
                return to_route($route->getName(), [
                    'team_hint' => Chief::GLOBAL_TEAM_HINT,
                ]);
            }
        }

        return to_route(config('chief.global_team_home'), [
            'team_hint' => Chief::GLOBAL_TEAM_HINT,
        ]);
    }
}

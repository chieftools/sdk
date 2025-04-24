<?php

namespace ChiefTools\SDK\Http\Controllers\Team;

use Illuminate\Http\Request;
use ChiefTools\SDK\Entities\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

class SwitchActive
{
    public function __invoke(Team $team): RedirectResponse
    {
        $user = authenticated_user_or_fail();

        $user->setCurrentTeam($team);

        $previousUrl = session()->previousUrl();

        if ($previousUrl !== null) {
            $previousRequest = Request::create($previousUrl);

            $route = Route::getRoutes()->match($previousRequest);

            // To prevent redirecting to a 404 page we check if the route is a generic route without any additional parameters next to the team hint
            if (count($route->parameters()) === 1 && $route->parameter('team_hint') !== null) {
                return to_route($route->getName(), [
                    'team_hint' => $team->slug,
                ]);
            }
        }

        return redirect()->to(home());
    }
}

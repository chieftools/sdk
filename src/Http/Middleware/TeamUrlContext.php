<?php

namespace ChiefTools\SDK\Http\Middleware;

use Closure;
use ChiefTools\SDK\Chief;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use ChiefTools\SDK\Http\Controllers\Team\Select;

/**
 * Sets the current team from the team slug in the URL. Requests without one use the team from the `X-Chief-Team` header,
 * which the command palette and web GraphQL clients send for the team of their page, or the last team opened in the session.
 *
 * The `select` hint asks the user to choose the team for the page. The `global` hint opens the global team view on
 * routes that allow it, it combines all teams of the user and has no current team, other routes ask to choose a team.
 *
 * Livewire component updates post to an endpoint without a team slug. Livewire runs this middleware again as persistent
 * middleware with a request for the page the component was rendered on, so every browser tab keeps the team of its page.
 */
class TeamUrlContext
{
    public function handle(Request $request, Closure $next): mixed
    {
        // The update endpoint has no team slug, the team is set when Livewire runs this middleware for the page of the component
        if (self::isLivewireUpdateRoute($request->route())) {
            return $next($request);
        }

        /** @var \ChiefTools\SDK\Entities\User|null $user */
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $isComponentUpdate = self::isLivewireUpdateRoute(request()->route());

        // The page of the component decides the team, not a team resolved earlier in this request from the session
        if ($isComponentUpdate) {
            $user->clearCurrentTeam();
        }

        $route     = $request->route();
        $routeHint = $route?->originalParameter('team_hint');

        if ($user->requestsTeamSelection($request)) {
            return app(Select::class)($request);
        }

        if ($user->requestsGlobalTeamView($request)) {
            return $this->handleGlobalTeamView($request, $next, $route, $routeHint);
        }

        $team = $user->currentTeam($request);

        // If the route slug doesn't match, redirect to the route with the correct team slug
        if ($routeHint !== null && $team->slug !== $routeHint) {
            return redirect()->to(str_replace_first("/{$routeHint}/", "/{$team->slug}/", $request->fullUrl()));
        }

        $route?->forgetParameter('team_hint');

        // Only opening a page of a team remembers it, component updates and requests with a team header keep it to the request so tabs on other teams don't take over the session
        $user->setCurrentTeam($team, remember: $routeHint !== null && !$isComponentUpdate);

        return $next($request);
    }

    /** The global team view combines all teams of the user on the routes that allow it, it is never remembered in the session. */
    private function handleGlobalTeamView(Request $request, Closure $next, ?Route $route, ?string $routeHint): mixed
    {
        /** @var \ChiefTools\SDK\Entities\User $user */
        $user = $request->user();

        if (Chief::allowsGlobalTeam($route)) {
            $route?->forgetParameter('team_hint');

            $user->setGlobalTeamView();

            return $next($request);
        }

        abort_if($routeHint === null, 404, 'The global team view is not available for this request.');

        // Links on pages of the global team view point to every route with the global team hint, the user chooses the team for pages that need one
        return redirect()->to(Chief::replaceTeamHint($request->fullUrl(), Chief::GLOBAL_TEAM_HINT, Chief::SELECT_TEAM_HINT));
    }

    /** Livewire finds its update endpoint the same way: the route name ends with `livewire.update`, custom routes can have a prefix. */
    private static function isLivewireUpdateRoute(?Route $route): bool
    {
        return str_ends_with($route?->getName() ?? '', 'livewire.update');
    }
}

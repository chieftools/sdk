<?php

namespace ChiefTools\SDK\Http\Controllers\Team;

use ChiefTools\SDK\Chief;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use ChiefTools\SDK\Entities\Team;
use ChiefTools\SDK\Entities\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\RedirectResponse;

/**
 * The user chooses the team to continue with, for a link to a page of any team: `/team/select/settings` opens the
 * settings of the chosen team. One team the user can open the page for is chosen right away.
 *
 * The team URL context shows it for the `select` team hint on every team route, so the page only exists for real routes.
 */
class Select
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        /** @var \ChiefTools\SDK\Entities\User $user */
        $user = $request->user();

        // Admins choose among the teams they are a member of, opening any other team stays an explicit choice
        $teams = $user->accessibleTeams()->get();

        $destinations = $teams
            ->filter(static fn (Team $team): bool => self::canOpen($user, $request->route(), $team))
            ->mapWithKeys(static fn (Team $team): array => [
                $team->id => Chief::replaceTeamHint($request->fullUrl(), Chief::SELECT_TEAM_HINT, $team->slug),
            ]);

        if ($destinations->containsOneItem()) {
            return redirect()->to($destinations->first());
        }

        return response()->view('chief::team.select', [
            'user'         => $user,
            'teams'        => $teams,
            'destinations' => $destinations,
        ]);
    }

    /** The route checks its abilities against the team with `can:{ability},team_hint` middleware. */
    private static function canOpen(User $user, ?Route $route, Team $team): bool
    {
        foreach ($route?->gatherMiddleware() ?? [] as $middleware) {
            if (!is_string($middleware) || !str_starts_with($middleware, 'can:')) {
                continue;
            }

            [$ability, $parameter] = array_pad(explode(',', substr($middleware, 4), 2), 2, null);

            if ($parameter === 'team_hint' && Gate::forUser($user)->denies($ability, $team)) {
                return false;
            }
        }

        return true;
    }
}

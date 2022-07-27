<?php

namespace ChiefTools\SDK\Http\Controllers\Team;

use Illuminate\Http\RedirectResponse;

class SwitchActive
{
    public function __invoke(string $teamSlug): RedirectResponse
    {
        /** @var \ChiefTools\SDK\Entities\Team $team */
        $team = auth()->user()->teams()->where('slug', '=', $teamSlug)->firstOrFail();

        session()->put('chief_team_slug', $team->slug);

        return redirect()->to(config('chief.auth.redirect'));
    }
}

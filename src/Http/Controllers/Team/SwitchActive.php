<?php

namespace ChiefTools\SDK\Http\Controllers\Team;

use Illuminate\Http\RedirectResponse;

class SwitchActive
{
    public function __invoke(string $teamSlug): RedirectResponse
    {
        /** @var \ChiefTools\SDK\Entities\User $user */
        $user = auth()->user();

        /** @var \ChiefTools\SDK\Entities\Team $team */
        $team = $user->teams()->where('slug', '=', $teamSlug)->firstOrFail();

        $user->setCurrentTeam($team);

        return redirect()->to(home());
    }
}

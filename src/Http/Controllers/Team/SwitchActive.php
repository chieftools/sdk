<?php

namespace ChiefTools\SDK\Http\Controllers\Team;

use ChiefTools\SDK\Entities\Team;
use Illuminate\Http\RedirectResponse;

class SwitchActive
{
    public function __invoke(Team $team): RedirectResponse
    {
        /** @var \ChiefTools\SDK\Entities\User $user */
        $user = auth()->user();

        $user->setCurrentTeam($team);

        return redirect()->to(home());
    }
}

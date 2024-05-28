<?php

namespace ChiefTools\SDK\Http\Controllers\Team;

use ChiefTools\SDK\Entities\Team;
use Illuminate\Http\RedirectResponse;

class SwitchActive
{
    public function __invoke(Team $team): RedirectResponse
    {
        $user = authenticated_user_or_fail();

        $user->setCurrentTeam($team);

        return redirect()->to(home());
    }
}

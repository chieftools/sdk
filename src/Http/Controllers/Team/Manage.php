<?php

namespace ChiefTools\SDK\Http\Controllers\Team;

use ChiefTools\SDK\Entities\Team;
use Illuminate\Http\RedirectResponse;

class Manage
{
    public function __invoke(Team $team): RedirectResponse
    {
        return redirect()->away(chief_base_url('teams'));
    }
}

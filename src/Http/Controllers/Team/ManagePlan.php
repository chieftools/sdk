<?php

namespace ChiefTools\SDK\Http\Controllers\Team;

use ChiefTools\SDK\Entities\Team;
use Illuminate\Http\RedirectResponse;

class ManagePlan
{
    public function __invoke(Team $team): RedirectResponse
    {
        $app = config('chief.id');

        abort_if($app === null, 404);

        return redirect()->away(chief_base_url("team/{$team->slug}/billing/{$app}"));
    }
}

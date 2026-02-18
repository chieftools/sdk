<?php

namespace ChiefTools\SDK\Http\Controllers\Team;

use ChiefTools\SDK\Entities\Team;
use Illuminate\Http\RedirectResponse;

class ManageInvoices
{
    public function __invoke(Team $team): RedirectResponse
    {
        return redirect()->away(chief_base_url("team/{$team->slug}/invoices"));
    }
}

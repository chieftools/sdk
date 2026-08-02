<?php

namespace ChiefTools\SDK\Http\Controllers\Pages;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class Legal
{
    public function __invoke(Request $request): RedirectResponse
    {
        return redirect()->away(chief_site_url('legal', query: $request->query()), 301);
    }
}

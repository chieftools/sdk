<?php

namespace ChiefTools\SDK\Http\Controllers\Pages;

use Illuminate\Http\RedirectResponse;

class Privacy
{
    public function __invoke(): RedirectResponse
    {
        return redirect()->away(chief_site_url('privacy'), 301);
    }
}

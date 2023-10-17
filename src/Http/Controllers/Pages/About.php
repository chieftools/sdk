<?php

namespace ChiefTools\SDK\Http\Controllers\Pages;

use Illuminate\Http\RedirectResponse;

class About
{
    public function __invoke(): RedirectResponse
    {
        return redirect()->away(chief_site_url('about'), 301);
    }
}

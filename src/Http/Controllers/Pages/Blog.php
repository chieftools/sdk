<?php

namespace ChiefTools\SDK\Http\Controllers\Pages;

use Illuminate\Http\RedirectResponse;

class Blog
{
    public function __invoke(): RedirectResponse
    {
        return redirect()->away(chief_site_url('blog'), 301);
    }
}

<?php

namespace IronGate\Integration\Http\Controllers\Pages;

use Illuminate\Http\RedirectResponse;

class Terms
{
    public function __invoke(): RedirectResponse
    {
        return redirect()->away(chief_site_url('terms'));
    }
}

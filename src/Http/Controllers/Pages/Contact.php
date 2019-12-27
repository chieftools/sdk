<?php

namespace IronGate\Integration\Http\Controllers\Pages;

use Illuminate\Http\RedirectResponse;

class Contact
{
    public function __invoke(): RedirectResponse
    {
        return redirect()->away(chief_site_url('contact'));
    }
}

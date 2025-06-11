<?php

namespace ChiefTools\SDK\Http\Controllers\Auth;

use RuntimeException;
use Illuminate\Http\RedirectResponse;

class Register
{
    public function __invoke(): RedirectResponse
    {
        if (empty($id = config('chief.id'))) {
            throw new RuntimeException('Missing app id (`chief.id`), cannot redirect to register endpoint.');
        }

        return redirect()->away(chief_base_url('register') . "&app={$id}");
    }
}

<?php

namespace ChiefTools\SDK\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;

class Login
{
    public function __invoke(): RedirectResponse
    {
        return Socialite::driver('chief')->stateless()->redirect();
    }
}

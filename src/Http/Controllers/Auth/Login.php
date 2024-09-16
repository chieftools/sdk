<?php

namespace ChiefTools\SDK\Http\Controllers\Auth;

use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;

class Login
{
    public function __invoke(): RedirectResponse
    {
        return Socialite::driver('chief')->redirect();
    }
}

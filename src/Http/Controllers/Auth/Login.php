<?php

namespace IronGate\Integration\Http\Controllers\Auth;

use Illuminate\Routing\Controller;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;

class Login extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function __invoke(): RedirectResponse
    {
        return Socialite::driver('chief')->redirect();
    }
}

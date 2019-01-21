<?php

namespace IronGate\Integration\Http\Controllers\Auth;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;

class Callback extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function __invoke(): RedirectResponse
    {
        /** @var \Laravel\Socialite\Two\User $remote */
        $remote = Socialite::driver('chief')->user();

        Auth::guard()->login(
            config('chief.auth.model')::createOrUpdateFromRemote($remote)
        );

        return redirect()->to(config('chief.auth.redirect'));
    }
}

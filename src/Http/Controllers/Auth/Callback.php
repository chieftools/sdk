<?php

namespace IronGate\Chief\Http\Controllers\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;

class Callback
{
    public function __invoke(): RedirectResponse
    {
        /** @var \IronGate\Chief\Socialite\ChiefUser $remote */
        $remote = Socialite::driver('chief')->user();

        Auth::guard()->login(
            config('chief.auth.model')::createOrUpdateFromRemote($remote)
        );

        return redirect()->intended(config('chief.auth.redirect'));
    }
}

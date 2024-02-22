<?php

namespace ChiefTools\SDK\Http\Controllers\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;

class Callback
{
    public function __invoke(): RedirectResponse
    {
        /** @var \ChiefTools\SDK\Socialite\ChiefProvider $driver */
        $driver = Socialite::driver('chief');

        /** @var \ChiefTools\SDK\Socialite\ChiefUser $remote */
        $remote = $driver->user();

        rescue(static fn () => $driver->revokeAccessToken($remote->token));

        Auth::guard()->login(
            config('chief.auth.model')::createOrUpdateFromRemote($remote),
        );

        return redirect()->intended(config('chief.auth.redirect'));
    }
}

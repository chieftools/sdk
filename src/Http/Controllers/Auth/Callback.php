<?php

namespace ChiefTools\SDK\Http\Controllers\Auth;

use ChiefTools\SDK\Chief;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;

class Callback
{
    public function __invoke(Request $request): RedirectResponse
    {
        if ($request->isNotFilled('code')) {
            $message = $request->filled('error_description')
                ? " ({$request->input('error_description')})"
                : '';

            return redirect()->to(home())->with('message', [
                'text' => "Authentication failed{$message}, please try again!",
                'type' => 'warning',
            ]);
        }

        /** @var \ChiefTools\SDK\Socialite\ChiefProvider $driver */
        $driver = Socialite::driver('chief');

        /** @var \ChiefTools\SDK\Socialite\ChiefUser $remote */
        $remote = $driver->user();

        $token = $remote->token;

        dispatch(static fn () => rescue(static function () use ($token) {
            /** @var \ChiefTools\SDK\Socialite\ChiefProvider $driver */
            $driver = Socialite::driver('chief');
            $driver->revokeAccessToken($token);
        }))->afterResponse();

        Auth::guard()->login(
            Chief::userModel()::createOrUpdateFromRemote($remote),
        );

        return redirect()->intended(config('chief.auth.redirect'));
    }
}

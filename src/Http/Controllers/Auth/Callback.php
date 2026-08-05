<?php

namespace ChiefTools\SDK\Http\Controllers\Auth;

use ChiefTools\SDK\Chief;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use ChiefTools\SDK\Socialite\ChiefProvider;
use Laravel\Socialite\Two\InvalidStateException;

class Callback
{
    public function __invoke(Request $request): RedirectResponse
    {
        if ($request->isNotFilled('code')) {
            return $this->authenticationFailed($request);
        }

        /** @var \ChiefTools\SDK\Socialite\ChiefProvider $driver */
        $driver = Socialite::driver('chief');

        try {
            /** @var \ChiefTools\SDK\Socialite\ChiefUser $remote */
            $remote = $driver->user();
        } catch (InvalidStateException $exception) {
            if (ChiefProvider::hasAuthenticationRetryState($request->input('state'))) {
                report($exception);

                return $this->authenticationFailed($request);
            }

            return $driver->redirectForAuthenticationRetry();
        }

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

    private function authenticationFailed(Request $request): RedirectResponse
    {
        $message = $request->filled('error_description')
            ? " ({$request->input('error_description')})"
            : '';

        return redirect()->to(home())->with('message', [
            'text' => "Authentication failed{$message}, please try again!",
            'type' => 'warning',
        ]);
    }
}

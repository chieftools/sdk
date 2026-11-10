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

        if (empty($remote->teams)) {
            return $this->authenticationFailed($request, 'Your account does not have access to applications.');
        }

        Auth::guard()->login(
            Chief::userModel()::createOrUpdateFromRemote($remote),
        );

        return redirect()->intended(config('chief.auth.redirect'));
    }

    private function authenticationFailed(Request $request, ?string $description = null): RedirectResponse
    {
        $description ??= $this->describeAuthorizationError($request);

        $message = $description !== null
            ? " ({$description})"
            : '';

        return redirect()->to(home())->with('message', [
            'text' => "Authentication failed{$message}, please try again!",
            'type' => 'warning',
        ]);
    }

    /**
     * Describe a failed authorization without reflecting the provider's error description.
     *
     * The callback query string can be crafted by anyone, so only known error codes map to a fixed description.
     */
    private function describeAuthorizationError(Request $request): ?string
    {
        if ($request->isNotFilled('error')) {
            return null;
        }

        logger()->info('chief.auth.authorization_error', [
            'error'             => $request->string('error')->limit(100)->toString(),
            'error_description' => $request->string('error_description')->limit(500)->toString(),
        ]);

        return match ($request->input('error')) {
            'access_denied' => 'The authorization request was denied',
            default         => null,
        };
    }
}

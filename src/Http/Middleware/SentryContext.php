<?php

namespace IronGate\Integration\Http\Middleware;

use Closure;
use Sentry\State\Scope;
use Illuminate\Http\Request;

class SentryContext
{
    public function handle(Request $request, Closure $next)
    {
        if (app()->bound('sentry')) {
            /** @var \Sentry\State\Hub $sentry */
            $sentry = app('sentry');

            /** @var \IronGate\Integration\Entities\User $user */
            $user = $request->user();

            $userData = $user ? [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ] : [
                'id' => null,
            ];

            $sentry->configureScope(function (Scope $scope) use ($userData) {
                $scope->setUser($userData);
            });
        }

        return $next($request);
    }
}

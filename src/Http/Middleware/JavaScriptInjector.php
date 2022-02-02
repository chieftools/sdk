<?php

namespace IronGate\Chief\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laracasts\Utilities\JavaScript\JavaScriptFacade as JavaScript;

class JavaScriptInjector
{
    public function handle(Request $request, Closure $next): mixed
    {
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        JavaScript::put([
            'ENV'            => app()->environment(),
            'CSRF'           => csrf_token(),
            'BASE'           => config('app.domain'),
            'HOME'           => url()->to('/'),
            'DEBUG'          => config('app.debug'),
            'SENTRY'         => [
                'DSN'                => config('sentry.public_dsn'),
                'TRACES_SAMPLE_RATE' => config('sentry.public_dsn') !== null ? config('sentry.traces_sample_rate', 0) : 0,
            ],
            'VERSION'        => config('app.version'),
            'VERSION_STRING' => config('app.versionString') . ' (' . config('app.version') . ')',
        ]);

        if (auth()->check()) {
            /** @var \IronGate\Chief\Entities\User $user */
            $user = auth()->user();

            /** @noinspection PhpMethodParametersCountMismatchInspection */
            JavaScript::put([
                'USER' => [
                    'id'       => $user->id,
                    'name'     => $user->name,
                    'email'    => $user->email,
                    'chief_id' => $user->chief_id,
                ],
            ]);
        }

        return $next($request);
    }
}

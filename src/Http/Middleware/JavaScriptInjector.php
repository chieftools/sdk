<?php

namespace ChiefTools\SDK\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laracasts\Utilities\JavaScript\JavaScriptFacade as JavaScript;

class JavaScriptInjector
{
    public function handle(Request $request, Closure $next): mixed
    {
        $sentryDsn = config('chief.analytics.sentry.public_dsn');

        /** @noinspection PhpMethodParametersCountMismatchInspection */
        JavaScript::put([
            'ENV'            => app()->environment(),
            'CSRF'           => csrf_token(),
            'BASE'           => config('app.domain'),
            'HOME'           => url()->to('/'),
            'USER'           => null,
            'TEAM'           => null,
            'DEBUG'          => config('app.debug'),
            'SENTRY'         => [
                'DSN'                       => $sentryDsn,
                'TUNNEL'                    => config('chief.analytics.sentry.public_tunnel'),
                'RELEASE'                   => config('sentry.release'),
                'TRACES_SAMPLE_RATE'        => $sentryDsn !== null ? config('chief.analytics.sentry.traces.sample_rate') : 0,
                'REPLAYS_SAMPLE_RATE'       => $sentryDsn !== null ? config('chief.analytics.sentry.replays.sample_rate') : 0,
                'REPLAYS_ERROR_SAMPLE_RATE' => $sentryDsn !== null ? config('chief.analytics.sentry.replays.error_sample_rate') : 0,
            ],
            'VERSION'        => config('app.version'),
            'REALTIME'       => [
                'AUTH'    => url()->to('broadcasting/auth'),
                'HOST'    => config('services.websockets.host'),
                'APPID'   => config('services.websockets.key'),
                'WSPORT'  => config('services.websockets.port'),
                'ENABLED' => config('services.websockets.enabled'),
            ],
            'VERSION_STRING' => config('app.versionString') . ' (' . config('app.version') . ')',
        ]);

        if (auth()->check()) {
            $user = authenticated_user_or_fail();

            /** @noinspection PhpMethodParametersCountMismatchInspection */
            JavaScript::put([
                'USER' => [
                    'id'       => $user->id,
                    'name'     => $user->name,
                    'email'    => $user->email,
                    'chief_id' => $user->chief_id,
                ],
                'TEAM' => config('chief.teams') ? [
                    'slug' => $user->team?->slug,
                ] : null,
            ]);
        }

        return $next($request);
    }
}

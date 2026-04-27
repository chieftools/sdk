<?php

namespace ChiefTools\SDK\Http\Controllers\Shell;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cookie;

class Theme
{
    public function __invoke(string $theme): JsonResponse
    {
        abort_unless(in_array($theme, ['light', 'dark', 'system'], true), 404);

        return response()
            ->json(['theme' => $theme])
            ->withCookie(Cookie::make(
                config('chief.shell.theme_cookie', 'chief_shell_theme'),
                $theme,
                60 * 24 * 365 * 5,
                '/',
                config('chief.shell.theme_cookie_domain') ?? config('session.domain'),
                config('session.secure'),
                true,
                false,
                config('session.same_site'),
            ));
    }
}

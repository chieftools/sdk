<?php

namespace ChiefTools\SDK\Helpers;

use Illuminate\Http\Request;

class HomeRouteResolver
{
    public function __invoke(Request $request): string
    {
        if ($request->user() === null) {
            return url()->to('/');
        }

        return url()->to(config('chief.auth.redirect'));
    }
}

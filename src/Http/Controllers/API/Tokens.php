<?php

namespace ChiefTools\SDK\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

class Tokens
{
    public function __invoke(): RedirectResponse
    {
        if (Route::has('api.docs')) {
            return redirect(route('api.docs') . '#authentication');
        }

        return redirect()->away(chief_base_url('api/tokens'));
    }

    public function create(Request $request): RedirectResponse
    {
        $queryParams = array_merge($request->query(), ['app' => config('chief.id')]);

        return redirect()->away(chief_base_url('api/token/create', query: $queryParams));
    }
}

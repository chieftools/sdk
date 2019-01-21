<?php

namespace IronGate\Integration\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;

class Logout
{
    public function __invoke(Request $request): RedirectResponse
    {
        Auth::guard()->logout();

        $request->session()->invalidate();

        return redirect('/');
    }
}

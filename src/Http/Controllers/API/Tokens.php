<?php

namespace IronGate\Chief\Http\Controllers\API;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class Tokens
{
    public function __invoke(Request $request): View
    {
        /** @var \IronGate\Chief\Entities\User $user */
        $user = $request->user();

        return view('chief::api.tokens.index', compact('user'));
    }

    public function delete(Request $request, string $token): RedirectResponse
    {
        /** @var \IronGate\Chief\Entities\User $user */
        $user = $request->user();

        $user->personalAccessTokens()->findOrFail($token)->forceDelete();

        return redirect()->route('api.tokens')->with('message', [
            'text' => 'Personal access token deleted succesfully!',
            'type' => 'success',
        ]);
    }
}

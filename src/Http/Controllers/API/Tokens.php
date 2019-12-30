<?php

namespace IronGate\Integration\Http\Controllers\API;

use Atrox\Haikunator;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use IronGate\Integration\Http\Requests\API\CreateToken;

class Tokens
{
    public function __invoke(Request $request): View
    {
        /** @var \IronGate\Integration\Entities\User $user */
        $user = $request->user();

        return view('chief::api.tokens.index', compact('user'));
    }

    public function create(): View
    {
        // Generate a random name for the token
        $name = ucwords(Haikunator::haikunate(['tokenLength' => 0, 'delimiter' => ' ']));

        return view('chief::api.tokens.create', compact('name'));
    }

    public function store(CreateToken $request): RedirectResponse
    {
        $token = $request->user()->createToken($request->input('name'));

        return redirect()->route('api.tokens')->with('access_token_id', $token->token->id)->with('access_token', $token->accessToken)->with('message', [
            'text' => 'Personal access token created succesfully!',
            'type' => 'success',
        ]);
    }

    public function delete(Request $request, string $token): RedirectResponse
    {
        /** @var \IronGate\Integration\Entities\User $user */
        $user = $request->user();

        $user->personalAccessTokens()->findOrFail($token)->forceDelete();

        return redirect()->route('api.tokens')->with('message', [
            'text' => 'Personal access token deleted succesfully!',
            'type' => 'success',
        ]);
    }
}

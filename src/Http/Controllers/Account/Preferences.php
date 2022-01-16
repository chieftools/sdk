<?php

namespace IronGate\Chief\Http\Controllers\Account;

use Illuminate\Http\Request;
use IronGate\Chief\Entities\User;
use Illuminate\Contracts\View\View;

class Preferences
{
    public function __invoke(Request $request): View
    {
        return view('chief::account.preferences', [
            'user'       => $request->user(),
            'categories' => User::getPreferencesByCategory(),
        ]);
    }

    public function toggle(Request $request): array
    {
        /** @var \IronGate\Chief\Entities\User $user */
        $user = $request->user();

        [, $preference] = explode(':', $request->input('identity'));

        $user->setPreference($preference, $request->input('state') === true);
        $user->save();

        return [
            'status' => 'OK!',
            'state'  => (bool)$user->getPreference($preference),
        ];
    }
}

<?php

namespace IronGate\Chief\Http\Controllers\Account;

use Illuminate\Http\Request;
use IronGate\Chief\Entities\User;

class Preferences
{
    public function __invoke(Request $request)
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

        // Extract the preference key from the identity
        [$_, $preference] = explode(':', $request->get('identity'));

        // Set the preference value
        $user->setPreference($preference, $request->get('state') === '1');

        // Persist the changes
        $user->save();

        return ['status' => 'OK!'];
    }
}

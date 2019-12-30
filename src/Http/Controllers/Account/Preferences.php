<?php

namespace IronGate\Integration\Http\Controllers\Account;

use Illuminate\Http\Request;
use IronGate\Integration\Entities\User;

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
        /** @var \IronGate\Integration\Entities\User $user */
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

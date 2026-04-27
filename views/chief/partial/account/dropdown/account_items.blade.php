@unless(config('chief.shell.variant') === 'modern')
    <div class="px-4 py-3" role="none">
        <p class="text-sm" role="none">
            Signed in as:
        </p>
        <p class="text-sm font-medium text-gray-900 truncate" role="none">
            {{ auth()->user() }}
        </p>
    </div>
@endunless

<div @class(['py-1' => config('chief.shell.variant') !== 'modern', 'border-t border-line p-1.5' => config('chief.shell.variant') === 'modern']) role="none">
    <x-chief::account.dropdown-link :href="route('account.profile')" icon="fa-user-circle">
        Profile
    </x-chief::account.dropdown-link>

    @if(ChiefTools\SDK\Entities\User::hasPreferences())
        <x-chief::account.dropdown-link :href="route('account.preferences')" icon="fa-cog">
            Preferences
        </x-chief::account.dropdown-link>
    @endif
</div>

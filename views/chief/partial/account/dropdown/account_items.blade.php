<div class="px-4 py-3" role="none">
    <p class="text-sm" role="none">
        Signed in as:
    </p>
    <p class="text-sm font-medium text-gray-900 truncate" role="none">
        {{ auth()->user() }}
    </p>
</div>
<div class="py-1" role="none">
    <x-chief::account.dropdown-link :href="route('account.profile')" icon="fa-user-circle">
        Profile
    </x-chief::account.dropdown-link>

    @if(IronGate\Chief\Entities\User::hasPreferences())
        <x-chief::account.dropdown-link :href="route('account.preferences')" icon="fa-cog">
            Preferences
        </x-chief::account.dropdown-link>
    @endif
</div>

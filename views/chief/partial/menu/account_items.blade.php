<x-chief::account.menu-link :href="route('account.profile')" :active="active('account/profile')" text="Profile" icon="fa-user-circle"/>

@if(ChiefTools\SDK\Entities\User::hasPreferences())
    <x-chief::account.menu-link :href="route('account.preferences')" :active="active('account/preferences')" text="Preferences" icon="fa-cog"/>
@endif

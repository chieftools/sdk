<div class="px-4 py-3" role="none">
    <p class="text-sm" role="none">
        Active team:
    </p>
    <p class="text-sm font-medium text-gray-900 truncate" role="none">
        {{ auth()->user()->team }}
    </p>
</div>
<div class="py-1" role="none">
    <x-chief::account.dropdown-link :href="route('team.chief.manage.plan', [auth()->user()->team])" icon="fa-credit-card" iconType="fad" target="_blank">
        Manage plan
    </x-chief::account.dropdown-link>
    <x-chief::account.dropdown-link :href="route('team.chief.manage.single', [auth()->user()->team])" icon="fa-gear" iconType="fad" target="_blank">
        Manage team
    </x-chief::account.dropdown-link>
</div>

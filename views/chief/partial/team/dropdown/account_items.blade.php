@unless(config('chief.shell.variant') === 'modern')
    <div class="px-4 py-3" role="none">
        <p class="text-sm" role="none">
            Active team:
        </p>
        <p class="text-sm font-medium text-gray-900 truncate" role="none">
            {{ auth()->user()->team }}
        </p>
    </div>
@else
    <div class="px-4 py-3" role="none">
        <div class="flex items-center gap-3">
            <img class="size-7 rounded-md" src="{{ auth()->user()->team->avatar_url }}" alt="">
            <div class="min-w-0 flex-1">
                <div class="truncate text-sm font-medium text-fg">{{ auth()->user()->team }}</div>
                <div class="truncate text-xs text-fg-subtle">{{ auth()->user()->team->slug }}</div>
            </div>
        </div>
    </div>
@endunless

<div @class(['py-1' => config('chief.shell.variant') !== 'modern', 'p-1.5' => config('chief.shell.variant') === 'modern']) role="none">
    <x-chief::account.dropdown-link :href="route('team.chief.manage.plan', [auth()->user()->team])" icon="fa-credit-card" iconType="fad" target="_blank">
        Manage plan
    </x-chief::account.dropdown-link>
    <x-chief::account.dropdown-link :href="route('team.chief.manage.single', [auth()->user()->team])" icon="fa-gear" iconType="fad" target="_blank">
        Manage team
    </x-chief::account.dropdown-link>
</div>

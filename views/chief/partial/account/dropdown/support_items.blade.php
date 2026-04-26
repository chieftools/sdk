<div @class(['py-1' => config('chief.shell.variant') !== 'modern', 'border-t border-gray-200 p-1.5 dark:border-white/10' => config('chief.shell.variant') === 'modern']) role="none">
    <x-chief::account.dropdown-link :href="chief_roadmap_url(config('chief.id') . '-menu-link')" icon="fa-road" target="_blank">
        Roadmap
    </x-chief::account.dropdown-link>
    <x-chief::account.dropdown-link :href="route('chief.contact')" icon="fa-paper-plane" target="_blank">
        Contact us
    </x-chief::account.dropdown-link>
</div>

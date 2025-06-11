<div class="py-1" role="none">
    <x-chief::account.dropdown-link :href="chief_roadmap_url(config('chief.id') . '-menu-link')" icon="fa-road" target="_blank">
        Roadmap
    </x-chief::account.dropdown-link>
    <x-chief::account.dropdown-link :href="route('chief.contact')" icon="fa-paper-plane" target="_blank">
        Contact us
    </x-chief::account.dropdown-link>
</div>

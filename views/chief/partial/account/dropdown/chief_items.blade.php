@php($_chief_apps = chief_apps())

@if($_chief_apps !== null && $_chief_apps->isNotEmpty())
    <div class="py-1" role="none">
        @foreach($_chief_apps->take(6) as $_chief_app)
            <x-chief::account.dropdown-link :href="$_chief_app['base_url']" :icon="$_chief_app['icon']" iconType="fad" :iconColor="$_chief_app['color']" target="_blank">
                {{ $_chief_app['name'] }}
            </x-chief::account.dropdown-link>
        @endforeach

        @if($_chief_apps->count() > 6)
            <x-chief::account.dropdown-link :href="chief_base_url()" icon="fa-toolbox" iconType="fad" target="_blank">
                All apps & tools
            </x-chief::account.dropdown-link>
        @endif
    </div>
@endif

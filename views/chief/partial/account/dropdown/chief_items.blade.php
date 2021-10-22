@php($_chief_apps = chief_apps())

@if($_chief_apps !== null && $_chief_apps->isNotEmpty())
    <div class="py-1" role="none">
        @foreach($_chief_apps->take(6) as $_chief_app)
            <a href="{{ $_chief_app['base_url'] }}" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" role="menuitem" tabindex="-1">
                <i class="mr-1 fad fa-fw {{ $_chief_app['icon'] }}" style="color: {{ $_chief_app['color'] }};"></i> {{ $_chief_app['name'] }}
            </a>
        @endforeach

        @if($_chief_apps->count() > 6)
            <a href="{{ config('chief.base_url') }}/" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" role="menuitem" tabindex="-1">
                <i class="mr-1 fad fa-fw fa-atom-alt" style="color: #34495e;"></i> All apps & tools
            </a>
        @endif
    </div>
@endif

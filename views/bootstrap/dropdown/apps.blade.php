@php($_chief_apps = chief_apps())

@if($_chief_apps !== null && $_chief_apps->isNotEmpty())
    <div class="dropdown-divider"></div>
    <h6 class="dropdown-header">
        Chief Tools
    </h6>

    @foreach($_chief_apps->take(5) as $_chief_app)
        <a class="dropdown-item" href="{{ $_chief_app['base_url'] }}">
            <i class="fal fa-fw {{ $_chief_app['icon'] }}" style="color: {{ $_chief_app['color'] }};"></i> {{ $_chief_app['name'] }}
        </a>
    @endforeach

    @if($_chief_apps->count() > 5)
        <a class="dropdown-item" href="{{ config('chief.base_url') }}/">
            <i class="fal fa-fw fa-atom-alt"></i> All apps & tools
        </a>
    @endif
@endif

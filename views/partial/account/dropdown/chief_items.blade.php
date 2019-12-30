@php($_chief_apps = chief_apps())

@if($_chief_apps !== null && $_chief_apps->isNotEmpty())
    <h6 class="dropdown-header">
        Chief Tools <small><a href="{{ config('chief.base_url') }}/" target="_blank" class="text-muted text-xs"><i class="fal fa-fw fa-external-link"></i></a></small>
    </h6>

    @foreach($_chief_apps->take(5) as $_chief_app)
        <a class="dropdown-item" href="{{ $_chief_app['base_url'] }}" target="_blank">
            <i class="fal fa-fw {{ $_chief_app['icon'] }}" style="color: {{ $_chief_app['color'] }};"></i> {{ $_chief_app['name'] }}
        </a>
    @endforeach

    @if($_chief_apps->count() > 5)
        <a class="dropdown-item" href="{{ config('chief.base_url') }}/" target="_blank">
            <i class="fal fa-fw fa-atom-alt"></i> All apps & tools
        </a>
    @endif

    <div class="dropdown-divider"></div>
@endif

<p class="mb-8 text-center text-base text-gray-400">
    <a href="{{ home() }}" class="text">
        @if(config('chief.brand.brandIcon'))
            <span class="fa-stack small text-brand" style="font-size: 0.5em;">
                <i class="fad {{ config('chief.brand.icon') }} fa-stack-2x"></i>
                <i class="fab {{ config('chief.brand.brandIcon') }} fa-stack-1x" style="font-size: 8px;"></i>
            </span>
            {{ config('app.name') }}
        @else
            <i class="fad fa-fw {{ config('chief.brand.icon') }} text-brand"></i> {{ config('app.name') }}
        @endif
    </a>
    &mdash;
    <a href="{{ chief_site_url() }}" class="text">
        <i class="fad fa-fw fa-toolbox"></i> Chief Tools
    </a>
</p>

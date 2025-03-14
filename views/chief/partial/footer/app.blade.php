<p class="mt-8 text-center leading-6 text-sm text-gray-500">
    <a href="{{ route('chief.about') }}" class="text-gray-500 hover:text-brand">
        @if(config('chief.brand.logoUrl'))
            <img class="max-h-4 max-w-4 inline align-text-top" src="{{ config('chief.brand.logoUrl') }}" alt="{{ config('app.title') }}">
        @else
            <i class="fad fa-fw {{ config('chief.brand.icon') }} text-brand"></i>
        @endif
        {{ config('app.name') }}
        is created in The Netherlands 🇳🇱
        <br>
        Hosted in Europe 🇪🇺
        &middot;
        A Chief Tools product.
    </a>
</p>

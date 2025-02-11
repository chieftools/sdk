@extendsfirst(['layout.default', 'chief::layout.html'], ['bodyClass' => $bodyClass ?? '', 'fullHeight' => true])

@section('body')
    <div class="min-h-full pt-16 pb-12 flex flex-col bg-white">
        <main class="flex grow flex-col justify-center max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-center text-brand">
                @if(config('chief.brand.logoUrl'))
                    <img class="max-h-8 max-w-8" src="{{ config('chief.brand.logoUrl') }}" alt="{{ config('app.title') }}">
                @elseif(config('chief.brand.brandIcon'))
                    <span class="fa-stack fa-2x">
                        <i class="fad {{ config('chief.brand.icon') }} fa-stack-2x"></i>
                        <i class="fab {{ config('chief.brand.brandIcon') }} fa-stack-1x" style="font-size: 16px;"></i>
                    </span>
                @else
                    <i class="fad fa-fw {{ config('chief.brand.icon') }} fa-4x" data-toggle="toolip" title="{{ config('app.title') }}"></i>
                @endif
            </div>
            <div class="py-16">
                <div class="text-center">
                    @include('chief::partial.alert')

                    @yield('content')

                    <div class="mt-6">
                        <a href="/" class="text-base font-medium text-brand-600 hover:text-brand-500">Go back home<span aria-hidden="true"> &rarr;</span></a>
                    </div>
                </div>
            </div>
        </main>
        <footer class="shrink-0 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8">
            @include('chief::partial.footer.app')

            @include('chief::partial.footer.links')

            @include('chief::partial.footer.version')
        </footer>
    </div>
@endsection

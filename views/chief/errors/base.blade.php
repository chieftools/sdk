@extends('chief::layout.html', ['bodyClass' => $bodyClass ?? '', 'fullHeight' => true])

@section('body')
    <div class="min-h-full pt-16 pb-12 flex flex-col bg-white">
        <main class="flex grow flex-col justify-center max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-center text-brand">
                <i class="fad fa-fw {{ config('chief.icon') }} fa-4x" data-toggle="toolip" title="{{ config('app.title') }}"></i>
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

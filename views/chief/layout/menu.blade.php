@extends('layout.default')

@section('content')
    <main class="mt-8 lg:grid lg:grid-cols-12 lg:gap-x-5">
        <aside class="py-6 px-2 sm:px-6 lg:py-0 lg:px-0 lg:col-span-3">
            <nav class="space-y-1">
                @include('chief::partial.menu_items')
            </nav>
        </aside>

        <div class="space-y-6 sm:px-6 lg:px-0 lg:col-span-9">
            @yield('maincontent')
        </div>
    </main>
@endsection

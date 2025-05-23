@if(!empty($title) && !is_array($title))
    @php($title = [$title])
@endif
<!DOCTYPE html>
<html lang="{{ config('app.locale') }}" class="antialiased {{ ($fullHeight ?? false) === true ? 'h-full' : '' }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="{{ config('chief.brand.color', '#2ecc71') }}">

        @stack('head.meta')

        @if(isset($canonical) && $canonical)
            <link rel="canonical" href="{{ $canonical }}">
        @else
            @php($currentPageNumber = is_numeric(request()->query('page')) ? (int)request()->query('page') : null)
            <link rel="canonical" href="{{ url()->current() }}{{ $currentPageNumber > 1 ? "?page={$currentPageNumber}" : '' }}">
        @endif

        <link rel="preconnect" href="{{ static_asset() }}" crossorigin="anonymous">

        @if(!isset($noTitle) || !$noTitle)
            @if(!empty($title))
                <title>{{ implode(' - ', array_filter(array_map('strip_tags', $title))) }} - {{ config('app.title') }}</title>
            @else
                <title>{{ config('app.title') }}</title>
            @endif
        @endif

        <link rel="icon" href="{{ static_asset('icons/' . config('chief.id') . '_favicon.svg') }}" type="image/svg+xml">
        <link rel="alternate icon" href="{{ static_asset('icons/' . config('chief.id') . '_favicon.ico') }}" sizes="32x32">

        @hasSection('styles')
            @yield('styles')
        @else
            @if(config('chief.assets.provider') === 'mix')
                <link media="all" type="text/css" rel="stylesheet" href="{{ asset(mix('css/app.min.css')) }}">
            @else
                @vite('resources/css/app.less')
            @endif
        @endif

        {!! Sentry\Laravel\Integration::sentryMeta() !!}

        @stack('head.style')
        @stack('head.script')
        @include('chief::layout.partial.jsvars')
    </head>
    <body class="{{ $bodyClass ?? '' }} {{ ($fullHeight ?? false) === true ? 'h-full' : '' }}">
        <div id="app" class="{{ ($fullHeight ?? false) === true ? 'h-full' : 'min-h-screen bg-gray-100 pb-16' }} border-t-4 border-brand">
            @yield('body')
        </div>

        @hasSection('scripts')
            @yield('scripts')
        @else
            @if(config('chief.assets.provider') === 'mix')
                <script src="{{ asset(mix('js/app.min.js')) }}"></script>
            @else
                @vite('resources/js/app.js')
            @endif
        @endif

        @stack('body.script')

        @include('chief::partial.external')
    </body>
</html>

@if(!empty($title) && !is_array($title))
@php
    $title = [$title];
@endphp
@endif
@php
    $themePreference = ChiefTools\SDK\Chief::themePreference();
    $theme = ChiefTools\SDK\Chief::theme();
@endphp
    <!DOCTYPE html>
<html lang="{{ config('app.locale') }}"
      data-theme="{{ $theme }}"
      data-theme-preference="{{ $themePreference }}"
      data-tool="{{ config('chief.id', 'chief') }}"
      style="--brand-color: {{ config('chief.brand.color', '#2ecc71') }}"
    @class([
        'antialiased',
        'dark' => $theme === 'dark',
        'h-full' => ($fullHeight ?? false) === true,
    ])>
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="{{ config('chief.brand.color') }}">
        <meta name="color-scheme" content="light dark">
        <meta name="supported-color-schemes" content="light dark">

        @if($themePreference === 'system')
            <script>
                (function(root) {
                    try {
                        var dark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                        root.classList.toggle('dark', dark);
                        root.dataset.theme = dark ? 'dark' : 'light';
                    } catch (error) {
                    }
                })(document.documentElement);
            </script>
        @endif

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

        <link rel="stylesheet" href="{{ url('/chief/navigate-track.css') }}?id={{ rawurlencode((string)config('app.version', '@dev')) }}" data-navigate-track>

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
        <div id="app" class="chief-layout-app {{ ($fullHeight ?? false) === true ? 'h-full' : 'min-h-screen pb-16' }} border-t-4 border-brand">
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

@if(!empty($title) && !is_array($title))
    @php($title = [$title])
@endif
<!DOCTYPE html>
<html lang="{{ config('app.locale') }}" class="{{ ($fullHeight ?? false) === true ? 'h-full' : '' }}">
    <head>
        <meta charset="utf-8"/>
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1"/>
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @if(auth()->check() && auth()->user()->is_admin && request('_refresh') !== 0)
            <meta http-equiv="refresh" content="{{ request('_refresh') }}"/>
        @endif

        @stack('head.meta')

        <link rel="preconnect" href="{{ static_asset() }}">

        @if(!empty($title))
            <title>{{ implode(' - ', array_map('strip_tags', $title)) }} - {{ config('app.title') }}</title>
        @else
            <title>{{ config('app.title') }}</title>
        @endif

        <link rel="icon" href="{{ static_asset('icons/' . config('chief.id') . '_favicon.svg') }}" type="image/svg+xml">
        <link rel="alternate icon" href="{{ static_asset('icons/' . config('chief.id') . '_favicon.ico') }}" sizes="32x32">

        @section('styles')
            <link media="all" type="text/css" rel="stylesheet" href="{{ asset(mix('css/app.css')) }}">
        @show

        @stack('head.style')
        @stack('head.script')
        @include('chief::layout.partial.jsvars')
    </head>
    <body class="{{ $bodyClass ?? '' }} {{ ($fullHeight ?? false) === true ? 'h-full' : '' }}">
        <div id="app" class="{{ ($fullHeight ?? false) === true ? 'h-full' : 'min-h-screen bg-gray-100' }} border-t-4 border-brand">
            @yield('body')
        </div>

        @section('scripts')
            <script src="{{ asset(mix('js/app.js')) }}"></script>
        @show

        @stack('body.script')

        @include('chief::partial.analytics')
    </body>
</html>

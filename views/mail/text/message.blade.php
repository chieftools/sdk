@props([
    'quickLinks' => [],
])
@php
    $quickLinks = collect($quickLinks)
        ->filter(fn ($link) => is_array($link) && filled($link['title'] ?? null) && filled($link['url'] ?? null))
        ->map(fn ($link) => [
            'title' => (string) $link['title'],
            'url'   => (string) $link['url'],
        ])
        ->values();
@endphp
<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
{{ config('app.name') }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{{ $slot }}

{{-- Quick Links --}}
@if($quickLinks->isNotEmpty())
<x-slot:quickLinks>
<x-mail::quick-links :links="$quickLinks" />
</x-slot:quickLinks>
@endif

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{{ $subcopy }}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
Mail sent by {{ config('app.name') }}.{{ config('app.name') === 'Chief Tools' ? '' : ' A Chief Tools product.' }}
@if(Illuminate\Support\Str::startsWith(config('app.versionString'), date('Y') . '.'))
© {{ config('app.versionString') }} ({{ config('app.version') }})
@else
© {{ date('Y') }} &mdash; {{ config('app.versionString') }} ({{ config('app.version') }})
@endif
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>

@props([
    'links' => [],
])
@php
    $links = collect($links)
        ->filter(fn ($link) => is_array($link) && filled($link['title'] ?? null) && filled($link['url'] ?? null))
        ->map(fn ($link) => [
            'title' => (string) $link['title'],
            'url'   => (string) $link['url'],
        ])
        ->values();
@endphp
@if($links->isNotEmpty())
Quick links:
@foreach($links as $link)
- {{ $link['title'] }}: {{ $link['url'] }}
@endforeach
@endif

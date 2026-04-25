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
<table class="quick-links" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="quick-links-label">Quick links:</td>
<td class="quick-links-items">
@foreach($links as $link)
<a href="{{ $link['url'] }}" target="_blank" rel="noopener">{{ $link['title'] }}</a>@unless($loop->last)<span class="quick-links-separator"> &middot; </span>@endunless
@endforeach
</td>
</tr>
</table>
@endif

@props([
    'color' => 'primary',
])
@php
    $color = in_array($color, ['primary', 'success', 'error', 'warning', 'muted'], true) ? $color : 'primary';
@endphp
<!-- {{ \ChiefTools\SDK\Mail\MarkdownBody::FULL_BLEED_START }} -->
<table class="band band-{{ $color }}" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="band-content">
{{ Illuminate\Mail\Markdown::parse($slot) }}
</td>
</tr>
</table>
<!-- {{ \ChiefTools\SDK\Mail\MarkdownBody::FULL_BLEED_END }} -->

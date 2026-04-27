@props([
    'href',
    'text',
    'icon' => 'fa-question-circle',
    'target' => null,
    'active' => false,
])

@php
    $classes = $active
        ? 'bg-surface-2 text-brand-600 hover:bg-surface-3'
        : 'text-fg hover:text-fg hover:bg-surface-2';
@endphp

<a href="{{ $href }}" @if($target) target="{{ $target }}" @endif class="{{ $classes }} group rounded-md px-3 py-2 flex items-center text-sm font-medium">
    <i class="fad fa-fw {{ $icon }} text-lg {{ $active ? 'text-brand-500' : 'text-fg-faint group-hover:text-fg-subtle' }} flex-shrink-0 -ml-1 mr-3"></i>
    <span class="truncate">{{ $text }}</span>
</a>

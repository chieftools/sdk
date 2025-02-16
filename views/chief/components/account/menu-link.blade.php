@props([
    'href',
    'text',
    'icon' => 'fa-question-circle',
    'target' => null,
    'active' => false,
])

@php
    $classes = $active
        ? 'bg-gray-50 text-brand-600 hover:bg-white'
        : 'text-gray-900 hover:text-gray-900 hover:bg-gray-50';
@endphp

<a href="{{ $href }}" @if($target) target="{{ $target }}" @endif class="{{ $classes }} group rounded-md px-3 py-2 flex items-center text-sm font-medium">
    <i class="fad fa-fw {{ $icon }} text-lg {{ $active ? 'text-brand-500' : 'text-gray-400 group-hover:text-gray-500' }} flex-shrink-0 -ml-1 mr-3"></i>
    <span class="truncate">{{ $text }}</span>
</a>

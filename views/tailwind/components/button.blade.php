@props([
    'href' => '#',
    'size' => null,
    'type' => null,
    'icon' => null,
    'disabled' => false,
    'iconType' => 'fal',
])

@php
    $classes = 'inline-flex font-medium shadow-sm ';

    $classes .= match($size) {
        'xs' => 'px-2.5 py-1.5 rounded ',
        'sm' => 'px-3 py-2 rounded-md leading-4 ',
        'lg' => 'px-6 py-3 rounded-md ',
        default => 'px-4 py-2 rounded-md ',
    };
    $classes .= $textClass = match($size) {
        'xs' => 'text-xs ',
        'md', 'lg' => 'text-base ',
        default => 'text-sm ',
    };
    $classes .= $disabled ? '' : 'focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 ';
    $classes .= match($type) {
        'white' => 'text-gray-700 bg-white hover:bg-gray-50 border border-transparent ',
        'outline' => 'text-gray-700 bg-white hover:text-white hover:bg-brand-700 border border-brand-600 ',
        default => 'text-white bg-brand-600 hover:bg-brand-700 border border-transparent ',
    };

    if ($disabled) {
        $href    = '#';
        $classes = str_replace('hover:', 'hover-disabled:', $classes);

        $classes .= 'cursor-not-allowed ';
    }
@endphp

<a {{ $attributes->merge(['class' => $classes, 'href' => $href]) }}>
    @if($icon)<i class="{{ $iconType }} fa-fw {{ $icon }} {{ $textClass }}"></i>&nbsp;@endif {{ $slot }}
</a>

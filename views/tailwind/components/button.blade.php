@props([
    'href' => '#',
    'size' => null,
    'type' => null,
    'icon' => null,
    'disabled' => false,
    'iconType' => 'fal',
])

@php
    $classes = 'inline-flex items-center font-medium shadow-xs ';

    $classes .= match($size) {
        'xxs' => 'px-1.5 py-1 rounded ',
        'xs' => 'px-2.5 py-1.5 rounded ',
        'lg' => 'px-4 py-2 rounded-md ',
        'xl' => 'px-6 py-3 rounded-md ',
        default => 'px-3 py-2 rounded-md leading-4 ',
    };
    $classes .= $textClass = match($size) {
        'xxs', 'xs' => 'text-xs ',
        'lg', 'xl' => 'text-base ',
        default => 'text-sm ',
    };
    $classes .= $disabled ? '' : 'focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 ';
    $classes .= match($type) {
        'white' => 'text-fg hover:text-fg bg-surface-2 hover:bg-surface-3 border border-line ',
        'outline' => 'text-fg bg-transparent hover:bg-accent hover:text-accent-fg border border-accent ',
        default => 'text-accent-fg hover:text-accent-fg bg-brand-600 hover:bg-brand-700 border border-transparent ',
    };

    if ($disabled) {
        $href    = '#';
        $classes = str_replace('hover:', 'hover-disabled:', $classes);

        $classes .= 'cursor-not-allowed ';
    }
@endphp

<a {{ $attributes->merge(['class' => $classes, 'href' => $href]) }}>
    @if($icon)
        <i class="{{ $iconType }} fa-fw {{ $icon }} mr-1.5"></i>
    @endif {{ $slot }}
</a>

@props([
    'href',
    'icon',
    'target' => null,
    'iconType' => 'fa',
    'iconColor' => null,
])

<a href="{{ $href }}" @if($target) target="{{ $target }}" @endif class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" role="menuitem" tabindex="-1">
    <i class="mr-1 {{ $iconType }} fa-fw {{ $icon }} text-gray-400 group-hover:text-gray-500" @if($iconColor) style="color: {{ $iconColor }}" @endif></i> {{ $slot }}
</a>

@props([
    'href',
    'icon' => null,
    'target' => null,
    'iconType' => 'fa',
    'iconColor' => null,
])

@if(config('chief.shell.variant') === 'modern')
    <a href="{{ $href }}"
       @if($target) target="{{ $target }}" @endif
       @if($target === '_blank') rel="noopener" @endif
       class="group flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-fg-muted transition hover:bg-surface-2 hover:text-fg"
       role="menuitem"
       tabindex="-1">
        @if($icon)
            <i class="{{ $iconType }} fa-fw {{ $icon }} text-fg-faint group-hover:text-fg-muted" @if($iconColor) style="color: {{ $iconColor }}" @endif></i>
        @endif
        <span class="min-w-0 flex-1 truncate">{{ $slot }}</span>
    </a>
@else
    <a href="{{ $href }}" @if($target) target="{{ $target }}" @endif class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" role="menuitem" tabindex="-1">
        @if($icon)<i class="mr-1 {{ $iconType }} fa-fw {{ $icon }} text-gray-400 group-hover:text-gray-500" @if($iconColor) style="color: {{ $iconColor }}" @endif></i> @endif{{ $slot }}
    </a>
@endif

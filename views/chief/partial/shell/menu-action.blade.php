@php
    $icon = $icon ?? null;
@endphp

<a href="{{ $href }}"
   class="group flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-fg-muted transition hover:bg-surface-2 hover:text-fg"
   @if(!empty($target)) target="{{ $target }}" @endif
   @if(!empty($target) && $target === '_blank') rel="noopener" @endif
>
    @if($icon)
        <i class="fa-fw {{ $icon }} text-fg-faint group-hover:text-fg-muted"></i>
    @endif
    <span class="min-w-0 flex-1 truncate">{{ $label }}</span>
</a>

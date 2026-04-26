@php
    $icon = $icon ?? null;
@endphp

<a href="{{ $href }}"
   class="group flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50 hover:text-gray-950 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100"
   @if(!empty($target)) target="{{ $target }}" @endif
   @if(!empty($target) && $target === '_blank') rel="noopener" @endif
>
    @if($icon)
        <i class="fa-fw {{ $icon }} text-gray-400 group-hover:text-gray-500 dark:text-gray-400 dark:group-hover:text-gray-200"></i>
    @endif
    <span class="min-w-0 flex-1 truncate">{{ $label }}</span>
</a>

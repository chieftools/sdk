@props([
    'icon' => null,
])

<button type="submit" {{ $attributes->merge(['class' => 'group px-3 py-2 text-sm rounded-md leading-4 border border-transparent font-medium text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500']) }}>
    @if($icon)
        <span class="-ml-1 pr-1">
            <i class="fa fa-fw {{ $icon }} text-brand-500 group-hover:text-brand-400"></i>
        </span>
    @endif
    {{ $slot }}
</button>

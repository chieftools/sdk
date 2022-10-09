@props([
    'icon' => null,
])

<button type="submit" {{ $attributes->merge(['class' => 'group py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500']) }}>
    @if($icon)
        <span class="-ml-1 pr-1">
            <i class="fa fa-fw {{ $icon }} text-brand-500 group-hover:text-brand-400"></i>
        </span>
    @endif
    {{ $slot }}
</button>

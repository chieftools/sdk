@props([
    'href' => '#',
])

<a href="{{ $href }}" class="group py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
    {{ $slot }}
</a>

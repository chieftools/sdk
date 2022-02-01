@props([
    'svg',
])

<x-tw::panel class="text-center shadow rounded prose prose-brand max-w-none">
    <div class="text-center mb-3">
        <figure class="hero-svg inline-block">
            {!! $svg !!}
        </figure>
    </div>

    <h3>
        {{ $title }}
    </h3>

    <p>
        {{ $slot }}
    </p>
</x-tw::panel>

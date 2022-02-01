@props([
    'code',
    'expanded' => null,
])

<p class="text-sm font-semibold text-brand-600 uppercase tracking-wide">{{ $code }} error</p>
<h1 class="mt-2 text-4xl font-extrabold text-gray-900 tracking-tight sm:text-5xl">{{ $slot }}</h1>
@if($expanded)
    <p class="mt-2 text-base text-gray-500">{{ $expanded }}</p>
@endif

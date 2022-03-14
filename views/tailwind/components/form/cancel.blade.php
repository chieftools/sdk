@props([
    'href' => null,
])

<x-tw::button type="white" :href="$href ?? url()->previous()">
    {{ $slot === null || $slot->isEmpty() ? 'Cancel' : $slot }}
</x-tw::button>

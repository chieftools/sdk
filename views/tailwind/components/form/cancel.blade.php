@props([
    'href' => null,
])

<x-tw::button type="white" :href="$href ?? url()->previous()">
    Cancel
</x-tw::button>

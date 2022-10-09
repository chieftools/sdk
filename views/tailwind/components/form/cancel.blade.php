<x-tw::button type="white" {{ $attributes->merge(['href' => url()->previous()]) }}>
    {{ $slot === null || $slot->isEmpty() ? 'Cancel' : $slot }}
</x-tw::button>

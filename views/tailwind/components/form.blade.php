@props([
    'method' => 'post',
])

<form method="{{ strtolower($method) === 'get' ? 'get' : 'post' }}" {{ $attributes }}>
    {{ $slot }}
    @unless(strtolower($method) === 'get')
        @method($method)
        @csrf
    @endunless
</form>

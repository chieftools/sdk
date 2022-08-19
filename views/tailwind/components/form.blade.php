@props([
    'class' => '',
    'action' => '',
    'method' => 'post',
])

<form action="{{ $action }}" method="{{ strtolower($method) === 'get' ? 'get' : 'post' }}" class="{{ $class }}">
    {{ $slot }}
    @unless(strtolower($method) === 'get')
        @method($method)
        @csrf
    @endunless
</form>

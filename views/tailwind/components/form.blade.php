@props([
    'class' => '',
    'action' => '',
    'method' => 'post',
])

<form action="{{ $action }}" method="{{ strtolower($method) === 'get' ? 'get' : 'post' }}" class="{{ $class }}">
    {{ $slot }}
    @method($method)
    @csrf
</form>

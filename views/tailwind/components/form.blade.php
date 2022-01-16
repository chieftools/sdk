@props([
    'class' => '',
    'action' => '',
    'method' => 'post',
])

<form action="{{ $action }}" method="{{ $method }}" class="{{ $class }}">
    {{ $slot }}
    @method($method)
    @csrf
</form>

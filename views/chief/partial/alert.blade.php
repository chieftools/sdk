{{-- Alert text is escaped, flash an Illuminate\Support\HtmlString to intentionally render trusted HTML --}}
@php
    $alertText = static fn (mixed $text, array $data = []) => $text instanceof Illuminate\Contracts\Support\Htmlable ? $text : __((string)$text, $data);
@endphp

@if(session()->has('status'))
    <x-tw::alert class="{{ $class ?? '' }}" :closable="true">
        {{ $alertText(session('status')) }}
    </x-tw::alert>
@endif

@if(session()->has('message'))
    <x-tw::alert class="{{ $class ?? '' }}" :type="session('message.type', 'info')" :closable="true">
        {{ $alertText(session('message.text'), session('message.data', [])) }}
    </x-tw::alert>
@endif

@if(isset($message))
    <x-tw::alert class="{{ $class ?? '' }}" :type="array_get($message, 'type', 'info')" :closable="true">
        {{ $alertText(array_get($message, 'text'), array_get($message, 'data', [])) }}
    </x-tw::alert>
@endif

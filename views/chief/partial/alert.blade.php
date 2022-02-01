@if(session()->has('status'))
    <x-tw::alert class="{{ $class ?? '' }}" :closable="true">
        {!! session('status') !!}
    </x-tw::alert>
@endif

@if(Session::has('message'))
    <x-tw::alert class="{{ $class ?? '' }}" :type="session('message.type', 'info')" :closable="true">
        @lang(session('message.text'), session('message.data', []))
    </x-tw::alert>
@endif

@if(isset($message))
    <x-tw::alert class="{{ $class ?? '' }}" :type="array_get($message, 'type', 'info')" :closable="true">
        @lang(array_get($message, 'text'), array_get($message, 'data', []))
    </x-tw::alert>
@endif

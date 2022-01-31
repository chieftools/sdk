@extends('chief::errors.base', ['title' => 'Page not found'])

@section('content')
    <x-errors.message code="404">
        Page not found

        <x-slot name="expanded">
            The page you requested does not exist or was moved.
        </x-slot>
    </x-errors.message>
@endsection

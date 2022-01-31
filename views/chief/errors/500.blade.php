@extends('chief::errors.base', ['title' => 'Internal server error'])

@section('content')
    <x-errors.message code="500">
        Internal server error

        <x-slot name="expanded">
            Something went wrong generating this page for you :(
        </x-slot>
    </x-errors.message>
@endsection

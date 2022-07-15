@extends('chief::errors.base', ['title' => 'Service Unavailable'])

@section('content')
    <x-chief::errors.message code="503">
        Service Unavailable

        <x-slot name="expanded">
            Right now we are bolting some nuts down and feeding our lab monkeys. We will be right back!
        </x-slot>
    </x-chief::errors.message>
@endsection

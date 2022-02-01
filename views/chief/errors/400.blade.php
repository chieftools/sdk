@extends('chief::errors.base', ['title' => 'Bad request'])

@section('content')
    <x-chief::errors.message code="400">
        Bad request

        <x-slot name="expanded">
            It looks like this request is not understandable by us :(
        </x-slot>
    </x-chief::errors.message>
@endsection

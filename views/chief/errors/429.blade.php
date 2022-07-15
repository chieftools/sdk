@extends('chief::errors.base', ['title' => 'Too Many Requests'])

@section('content')
    <x-chief::errors.message code="429">
        Too Many Requests

        <x-slot name="expanded">
            Sorry, you are making too many requests, wait a bit before trying again later!
        </x-slot>
    </x-chief::errors.message>
@endsection

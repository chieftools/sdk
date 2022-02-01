@extends('chief::errors.base', ['title' => 'Method not allowed'])

@section('content')
    <x-chief::errors.message code="405">
        Method not allowed

        <x-slot name="expanded">
            It looks like this request was made with the wrong HTTP method.
        </x-slot>
    </x-chief::errors.message>
@endsection

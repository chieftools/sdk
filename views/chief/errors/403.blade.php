@extends('chief::errors.base', ['title' => 'Forbidden'])

@section('content')
    <x-chief::errors.message code="403">
        Forbidden

        <x-slot name="expanded">
            You are not allowed to access this resource.
        </x-slot>
    </x-chief::errors.message>
@endsection

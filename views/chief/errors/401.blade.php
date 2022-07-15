@extends('chief::errors.base', ['title' => 'Unauthorized'])

@section('content')
    <x-chief::errors.message code="401">
        Unauthorized

        <x-slot name="expanded">
            You are not authorized to access this resource.
        </x-slot>
    </x-chief::errors.message>
@endsection

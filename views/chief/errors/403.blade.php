@extends('chief::errors.base', ['title' => 'Forbidden'])

@section('content')
    <x-errors.message code="403">
        Forbidden

        <x-slot name="expanded">
            It looks like you are not allowed to access this resource.
        </x-slot>
    </x-errors.message>
@endsection

@extends('chief::errors.base', ['title' => 'Unauthorized'])

@section('content')
    <x-chief::errors.message code="401">
        Unauthorized

        <x-slot name="expanded">
            It looks like you are not allowed to access the resource.
        </x-slot>
    </x-chief::errors.message>
@endsection

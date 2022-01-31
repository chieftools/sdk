@extends('chief::errors.base', ['title' => 'Unauthorized'])

@section('content')
    <x-errors.message code="401">
        Unauthorized

        <x-slot name="expanded">
            It looks like you are not allowed to access the resource.
        </x-slot>
    </x-errors.message>
@endsection

@extends('chief::errors.base', ['title' => 'Gone'])

@section('content')
    <x-chief::errors.message code="410">
        Gone

        <x-slot name="expanded">
            The requested resource is no longer available.
        </x-slot>
    </x-chief::errors.message>
@endsection

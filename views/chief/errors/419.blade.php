@extends('chief::errors.base', ['title' => 'Page expired'])

@section('content')
    <x-chief::errors.message code="419">
        Page expired

        <x-slot name="expanded">
            Sorry, your session has expired. Please refresh and try again.
        </x-slot>
    </x-chief::errors.message>
@endsection

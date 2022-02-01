@extends('chief::errors.base', ['title' => 'Be right back!'])

@section('content')
    <x-chief::errors.message code="503">
        Be right back!

        <x-slot name="expanded">
            Right now we are bolting some nuts down and feeding our lab monkeys.
        </x-slot>
    </x-chief::errors.message>
@endsection

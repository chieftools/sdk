@extends('chief::errors.base', ['title' => 'Bad request'])

@section('content')
    <x-chief::errors.message code="400">
        Bad request

        <x-slot name="expanded">
            @if($exception instanceof Symfony\Component\HttpKernel\Exception\HttpException && $exception->getMessage())
                {{ $exception->getMessage() }}
            @else
                This request is not understandable by us :(
            @endif
        </x-slot>
    </x-chief::errors.message>
@endsection

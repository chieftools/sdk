@extends('chief::errors.base', ['title' => 'Forbidden'])

@section('content')
    <x-chief::errors.message code="403">
        Forbidden

        <x-slot name="expanded">
            @if($exception instanceof Symfony\Component\HttpKernel\Exception\HttpException && $exception->getMessage())
                {{ $exception->getMessage() }}
            @else
                You are not allowed to access this resource.
            @endif
        </x-slot>
    </x-chief::errors.message>
@endsection

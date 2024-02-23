@extends('chief::errors.base', ['title' => 'Unauthorized'])

@section('content')
    <x-chief::errors.message code="401">
        Unauthorized

        <x-slot name="expanded">
            @if($exception instanceof Symfony\Component\HttpKernel\Exception\HttpException && $exception->getMessage())
                {{ $exception->getMessage() }}
            @else
                You are not authorized to access this resource.
            @endif
        </x-slot>
    </x-chief::errors.message>
@endsection

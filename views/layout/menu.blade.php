@extends('layout.default')

@section('content')
    <div class="row mt-md-5 mt-2">
        <div class="col-sm-3">
            <ul class="nav nav-bordered nav-vertical flex-column mb-4">
                @include('chief::partial.menu_items')
            </ul>
        </div>
        <div class="col-sm-9">
            @yield('maincontent')
        </div>
    </div>
@endsection

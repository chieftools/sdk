@extends('layout.default', ['title' => 'New personal access token - Developer'])

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            {!! Former::open() !!}
                <div class="card mt-4 mb-4">
                    <div class="card-header">
                        <i class="fal fa-fw fa-key"></i> New personal access tokens
                    </div>
                    <div class="card-body">
                        {!! Former::text('name')->autofocus()->value($name) !!}
                    </div>
                    <div class="card-footer text-right">
                        <a href="{{ route('api.tokens') }}" class="btn btn-sm btn-light">
                            Cancel
                        </a>

                        {!! Former::submit('Create')->addClass('btn-outline-success btn-sm') !!}
                    </div>
                </div>
            {!! Former::close() !!}
        </div>
    </div>
@endsection

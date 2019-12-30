@extends('chief::layout.account', ['title' => 'Profile'])

@section('maincontent')
    @component('chief::components.page-header', ['nomargin' => true])
        <i class="fad fa-fw fa-user-circle text-muted"></i> Profile</small>
    @endcomponent

    <div class="row justify-content-center">
        <div class="col-lg-12">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="alert alert-info">
                        <h4 class="alert-heading">Your profile is managed by Chief Tools!</h4>
                        <p>
                            To update your name, email and password settings open the Chief Tools dashboard.
                        </p>
                        <hr>
                        <a href="{{ chief_base_url('profile') }}" target="_blank" class="btn btn-sm btn-primary">
                            <i class="fal fa-fw fa-external-link-square-alt"></i> Chief Tools
                        </a>
                    </div>

                    {!! Former::open() !!}
                    {!! Former::populate(auth()->user()) !!}

                        {!! Former::text('name')->readonly() !!}

                        {!! Former::email('email')->label('E-mail')->readonly() !!}

                        {!! Former::text('timezone')->value(config('app.timezone'))->readonly() !!}

                    {!! Former::close() !!}
                </div>
            </div>
        </div>
    </div>
@endsection

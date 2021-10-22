@extends('chief::layout.developer', ['title' => 'Personal access tokens'])

@section('maincontent')
    @component('chief::components.page-header', ['nomargin' => true])
        <i class="fad fa-fw fa-key text-muted"></i> Personal access tokens
    @endcomponent

    <div class="row justify-content-center">
        <div class="col-lg-12">
            @if(session()->has('access_token'))
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fal fa-fw fa-key"></i> New access token <b>{{ $user->personalAccessTokens->find(session()->get('access_token_id'))->name }}</b>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <i class="fad fa-fw fa-exclamation-circle"></i> This token will only be shown once! If you lose it you can disable the token and create a new one.
                        </div>

                        <div class="input-group">
                            <input id="access_token" class="form-control" type="text" value="{{ session()->get('access_token') }}" readonly>
                            <div class="input-group-append">
                                <button id="accesss_token_copy" class="btn btn-outline-secondary" type="button" data-clipboard-target="#access_token" data-toggle="tooltip" data-placement="top" title="Copied!" data-trigger="click">
                                    <i class="fal fa-fw fa-copy"></i>
                                </button>
                                @push('body.script')
                                    <script>
                                        new ClipboardJS('#accesss_token_copy');
                                        jQuery('#accesss_token_copy').on('show.bs.tooltip', function (e) {
                                            var button = jQuery(this);
                                            setTimeout(function () {
                                                button.tooltip('hide');
                                            }, 500);
                                        });
                                    </script>
                                @endpush
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="card mb-4">
                <div class="card-body">
                    These tokens can be used to access the API authenticated as your account without having to create an OAuth2 client and authorize your account through that client.
                </div>
                @if($user->personalAccessTokens->isNotEmpty())
                    <ul class="list-group list-group-flush">
                        @foreach($user->personalAccessTokens as $token)
                            <li class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-0">{{ $token->name }}</h6>
                                </div>
                                <small class="text-muted">
                                    Created
                                    <time data-toggle="tooltip" title="{{ $token->created_at->format('d-m-Y H:i:s') }}" datetime="{{ $token->created_at->toIso8601String() }}">
                                        {{ $token->created_at->diffForHumans() }}
                                    </time>
                                    &middot;
                                    Expires
                                    <time data-toggle="tooltip" title="{{ $token->expires_at->format('d-m-Y H:i:s') }}" datetime="{{ $token->expires_at->toIso8601String() }}">
                                        {{ $token->expires_at->diffForHumans() }}
                                    </time>
                                </small>
                                <br>
                                <a href="{{ route('api.tokens.delete', [$token->id]) }}" data-confirm="true" data-method="post" class="btn btn-xs btn-outline-danger inline-block mt-1">
                                    <i class="fal fa-fw fa-trash-alt"></i> Disable token
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="card-body pt-0">
                        <div class="alert alert-info mb-0 mt-0">
                            <i class="fad fa-fw fa-exclamation-circle"></i> You don't have any active personal access tokens.
                        </div>
                    </div>
                @endif
                <div class="card-footer {{ $user->personalAccessTokens->isNotEmpty() ? 'border-top-0' : '' }}">
                    <a href="{{ route('api.tokens.create') }}" class="btn btn-sm btn-outline-success float-right">
                        <i class="fal fa-fa fa-plus"></i> Create new token
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

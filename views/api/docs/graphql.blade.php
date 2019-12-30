@extends('chief::layout.developer', ['title' => 'GraphQL API'])

@section('maincontent')
    @component('chief::components.page-header', ['nomargin' => true])
        <i class="fad fa-fw fa-plug text-muted"></i> GraphQL API</small>
    @endcomponent

    <div class="row justify-content-center">
        <div class="col-lg-12">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fal fa-fw fa-books"></i> Documentation
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fa fa-fw fa-exclamation-circle"></i> The API is in <b>beta</b>, it <b>can and will</b> change it's schema in a <b>breaking</b> manner <b>without notice</b>!
                    </div>

                    The API is based on the <a href="https://graphql.org/learn/" target="_blank" rel="noopener">GraphQL</a> query language and lives here: <code>{{ route('api') }}</code>.

                    <br>
                    <br>

                    Currently there is no full documentation yet since the API is in constant flux, however introspection queries are enabled and you can view the generated documentation from inside the playground. If you want you can even download the full <a href="{{ route('api.schema') }}" target="_blank">GraphQL schema file</a>.

                    <br>
                    <br>

                    You can use the playground to poke around in the API, it has a documentation sidebar you can use to figure out which information is available and how to obtain it.

                    <br>
                    <br>

                    <span class="text-danger">
                        <i class="fal fa-fw fa-exclamation-triangle"></i> When using the playground, be aware you are on the production API and you are modifiying your own data!
                    </span>
                </div>
                <div class="card-footer text-right">
                    <a href="{{ route('api.playground') }}" class="btn btn-sm btn-outline-primary">
                        <i class="fal fa-fw fa-rocket"></i> Open playground
                    </a>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <i class="fal fa-fw fa-key"></i> Authorization
                </div>
                <div class="card-body">
                    Authorization is handled through <a href="https://oauth.net/2/" target="_blank" rel="noreferrer">OAuth 2.0</a>, you can <a href="{{ route('api.tokens') }}">generate</a> personal access tokens to use the API for your own account. If you are interested in authenticating {{ config('app.name') }} users directly from your application using a OAuth 2.0 application <a href="{{ route('chief.contact') }}">let us know</a>!

                    <br>
                    <br>

                    If you want to play around with the API from your own tooling and/or application you can <a href="{{ route('api.tokens') }}">generate</a> a personal access token and pass it using the <code>Authorization</code> header.

                    <br>
                    <br>

                    <pre class="mb-0"><code>## {{ config('app.title') }}@if(config('app.beta')) &beta;@endif GraphQL request
curl -X "POST" "<span class="text-primary">{{ route('api') }}</span>" \
     -H 'Accept: application/json' \
     -H 'Authorization: Bearer <span class="text-primary">&lt;personal access token&gt;</span>' \
     -H 'Content-Type: application/x-www-form-urlencoded; charset=utf-8' \
     --data-urlencode "query={viewer{name}}"</code></pre>
                </div>
            </div>
        </div>
    </div>
@endsection

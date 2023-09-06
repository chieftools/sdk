@extends('chief::layout.developer', ['title' => 'GraphQL API'])

@section('maincontent')
    <x-tw::panel icon="fa-books" title="Documentation">
        <x-tw::alert type="warning">
            The API is in <b>beta</b>, it <b>can and will</b> change it's schema in a <b>breaking</b> manner <b>without notice</b>!
        </x-tw::alert>

        <div class="prose-sm prose-brand max-w-none mt-3 mb-3">
            <p>
                The API is based on the <a href="https://graphql.org/learn/" target="_blank" rel="noopener" class="font-medium text-brand-600 hover:text-brand-500">GraphQL</a> query language and lives here: <code>{{ route('api') }}</code>.
            </p>
            <p>
                Currently there is no full documentation yet since the API is in constant flux, however introspection queries are enabled and you can view the generated documentation from inside the playground. If you want you can even download the full <a href="{{ route('api.schema') }}" target="_blank" class="font-medium text-brand-600 hover:text-brand-500">GraphQL schema file</a>.
            </p>
            <p>
                You can use the playground to poke around in the API, it has a documentation sidebar you can use to figure out which information is available and how to obtain it.
            </p>
        </div>

        <x-tw::alert type="danger">
            When using the playground, be aware you are on the production API and you are modifiying your own data!
        </x-tw::alert>

        <x-slot name="footer">
            <x-tw::button :href="route('api.playground')" icon="fa-rocket">
                Open playground
            </x-tw::button>
        </x-slot>
    </x-tw::panel>

    <x-tw::panel icon="fa-key" title="Authorization">
        <div class="prose-sm prose-brand max-w-none">
            <p>
                Authorization is handled through <a href="https://oauth.net/2/" target="_blank" rel="noreferrer" class="font-medium text-brand-600 hover:text-brand-500">OAuth 2.0</a>, you can <a href="{{ route('api.tokens') }}" class="font-medium text-brand-600 hover:text-brand-500">generate</a> personal access tokens to use the API for your own account. If you are interested in authenticating {{ config('app.name') }} users directly from your application using a OAuth 2.0 application <a href="{{ route('chief.contact') }}">let us know</a>!
            </p>
            <p>
                If you want to play around with the API from your own tooling and/or application you can <a href="{{ route('api.tokens') }}" class="font-medium text-brand-600 hover:text-brand-500">generate</a> a personal access token and pass it using the <code>Authorization</code> header.
            </p>
        </div>

        <hr class="my-4">

        <pre class="leading-6 text-sm"><code>## {{ config('app.title') }}@if(config('app.beta')) &beta;@endif GraphQL request
curl -X "POST" "<span class="text-brand">{{ route('api') }}</span>" \
     -H 'Accept: application/json' \
     -H 'Authorization: Bearer <span class="text-brand">&lt;personal access token&gt;</span>' \
     -H 'Content-Type: application/x-www-form-urlencoded; charset=utf-8' \
     --data-urlencode "query={ viewer { name } }"</code></pre>
    </x-tw::panel>
@endsection

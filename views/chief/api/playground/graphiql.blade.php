@extendsfirst(['layout.base', 'chief::layout.html'], ['title' => 'Playground - API', 'fullHeight' => true])

@push('head.meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@push('head.style')
    <style>
        html, body {
            height: 100%;
        }

        body > nav.navbar {
            z-index: 10;
        }

        #graphiql {
            height: {{ config('debugbar.enabled') ? 'calc(100vh - 61px - 35px)' : 'calc(100vh - 61px)' }};
        }
    </style>
@endpush

@push('head.script')
    @php
        // Self-hosted bundle copied to `public/js/graphiql` by the `@chieftools/sdk/vite` plugin, resolved
        // with its own Vite instance so the app's build directory and hot file are left untouched
        $graphiql = new Illuminate\Foundation\Vite;
        $graphiql->useBuildDirectory('js/graphiql')->useHotFile(public_path('js/graphiql/hot'));

        if (($nonce = Illuminate\Support\Facades\Vite::cspNonce()) !== null) {
            $graphiql->useCspNonce($nonce);
        }
    @endphp
    {{ $graphiql('index.js') }}
@endpush

@section('body')
    @include('partial.menu', ['fullwidthMenu' => true])

    <div class="container-fluid p-0" style="margin-top: 1px; height: calc(100% - 57px);">
        <div id="graphiql" data-endpoint="{{ route('api.web') }}" @if(config('chief.graphql.subscriptions.enabled')) data-subscriptions @endif>
            <div style="text-align: center; margin-top: 20px;">
                <i class="fal fa-3x fa-fw fa-circle-notch fa-spin"></i>
            </div>
        </div>
    </div>
@endsection

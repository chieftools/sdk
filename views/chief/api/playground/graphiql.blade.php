@extendsfirst(['layout.html', 'chief::layout.html'], ['title' => 'Playground - API', 'fullHeight' => true])

@push('head.meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@push('head.style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/graphiql/graphiql.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@graphiql/plugin-explorer/dist/style.css" />
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

@section('body')
    @include('partial.menu', ['fullwidthMenu' => true])

    <div class="container-fluid p-0" style="margin-top: 1px; height: calc(100% - 57px);">
        <div id="graphiql">
            <div style="text-align: center; margin-top: 20px;">
                <i class="fal fa-3x fa-fw fa-circle-notch fa-spin"></i>
            </div>
        </div>
    </div>
@endsection

@push('body.script')
    <script src="https://cdn.jsdelivr.net/npm/react@18/umd/react.production.min.js" crossorigin></script>
    <script src="https://cdn.jsdelivr.net/npm/react-dom@18/umd/react-dom.production.min.js" crossorigin></script>
    <script src="https://cdn.jsdelivr.net/npm/graphiql/graphiql.min.js" crossorigin></script>
    <script src="https://cdn.jsdelivr.net/npm/@graphiql/plugin-explorer/dist/index.umd.js" crossorigin></script>
    <script>
        // Default the theme to 'light' to match the application style
        const storedTheme = localStorage.getItem('graphiql:theme');
        if (!storedTheme) {
            localStorage.setItem('graphiql:theme', 'light');
        }

        const root = ReactDOM.createRoot(document.getElementById('graphiql'));

        const fetcher = GraphiQL.createFetcher({
            url:     '{{ route('api.web') }}',
            headers: {
                'Accept':       'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                ...(window.TEAM && window.TEAM.slug ? {
                    'X-Chief-Team': window.TEAM.slug,
                } : {}),
            },
        });

        const explorerPlugin = GraphiQLPluginExplorer.explorerPlugin();

        root.render(
            React.createElement(GraphiQL, {
                fetcher,
                plugins:                      [explorerPlugin],
                defaultQuery:                 '{\n' +
                                                  '  viewer {\n' +
                                                  '    name\n' +
                                                  '  }\n' +
                                                  '}\n',
                introspectionQueryName:       'playgroundIntrospectionQuery',
                defaultEditorToolsVisibility: true,
            }),
        );
    </script>
@endpush

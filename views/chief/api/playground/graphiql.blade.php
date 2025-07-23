@extendsfirst(['layout.base', 'chief::layout.html'], ['title' => 'Playground - API', 'fullHeight' => true])

@push('head.meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@push('head.style')
    <link rel="stylesheet" href="https://esm.sh/graphiql/dist/style.css" />
    <link rel="stylesheet" href="https://esm.sh/@graphiql/plugin-explorer/dist/style.css" />
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
    <script type="importmap">
        {
          "imports": {
            "react": "https://esm.sh/react@19.1.0",
            "react/": "https://esm.sh/react@19.1.0/",

            "react-dom": "https://esm.sh/react-dom@19.1.0",
            "react-dom/": "https://esm.sh/react-dom@19.1.0/",

            "graphiql": "https://esm.sh/graphiql?standalone&external=react,react-dom,@graphiql/react,graphql",
            "graphiql/": "https://esm.sh/graphiql/",
            "@graphiql/plugin-explorer": "https://esm.sh/@graphiql/plugin-explorer?standalone&external=react,@graphiql/react,graphql",
            "@graphiql/react": "https://esm.sh/@graphiql/react?standalone&external=react,react-dom,graphql,@graphiql/toolkit,@emotion/is-prop-valid",

            "@graphiql/toolkit": "https://esm.sh/@graphiql/toolkit?standalone&external=graphql",
            "graphql": "https://esm.sh/graphql@16.11.0",
            "@emotion/is-prop-valid": "data:text/javascript,"
          }
        }
    </script>
    <script type="module">
        import React from 'react';
        import ReactDOM from 'react-dom/client';
        import {GraphiQL, HISTORY_PLUGIN} from 'graphiql';
        import {createGraphiQLFetcher} from '@graphiql/toolkit';
        import {explorerPlugin} from '@graphiql/plugin-explorer';
        import 'graphiql/setup-workers/esm.sh';

        // Default the theme to 'light' to match the application style
        const storedTheme = localStorage.getItem('graphiql:theme');
        if (!storedTheme) {
            localStorage.setItem('graphiql:theme', 'light');
        }

        const fetcher = createGraphiQLFetcher({
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

        function App() {
            return React.createElement(GraphiQL, {
                fetcher,
                plugins:                      [HISTORY_PLUGIN, explorerPlugin()],
                defaultQuery:                 '{\n' +
                                                  '  viewer {\n' +
                                                  '    name\n' +
                                                  '  }\n' +
                                                  '}\n',
                introspectionQueryName:       'playgroundIntrospectionQuery',
                defaultEditorToolsVisibility: true,
            });
        }

        const container = document.getElementById('graphiql');
        const root      = ReactDOM.createRoot(container);

        root.render(React.createElement(App));
    </script>
@endpush

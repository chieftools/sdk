@extendsfirst(['layout.html', 'chief::layout.html'], ['title' => 'Playground - API'])

@push('head.meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@push('head.style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/graphiql@0.17.5/graphiql.min.css" integrity="sha256-8kB93cV/bRj11xrCZtF0PrMUoFgmVT4DBf3CKiiF28Q=" crossorigin="anonymous">
    <style>
        html, body {
            height: 100%;
        }

        .editorWrap {
            overflow: hidden;
        }

        body > nav.navbar {
            z-index: 10;
        }

        #graphiql {
            height: {{ config('debugbar.enabled') ? 'calc(100vh - 60px - 28px)' : 'calc(100vh - 60px)' }};
        }

        #graphiql .title {
            display: none;
        }

        #graphiql .topBar {
            min-height: 47px;
        }

        #graphiql .history-title-bar, #graphiql .doc-explorer-title-bar {
            min-height: 50px;
        }

        #graphiql .query-editor .CodeMirror {
            padding: 0 20px;
            height: 100% !important;
        }

        #graphiql .result-window .CodeMirror {
            left: 20px;
            padding: 0;
            height: 100% !important;
        }
    </style>
@endpush

@push('body.script')
    <script src="//cdn.jsdelivr.net/es6-promise/4.0.5/es6-promise.auto.min.js"></script>
    <script src="//cdn.jsdelivr.net/fetch/0.9.0/fetch.min.js"></script>
    <script src="//cdn.jsdelivr.net/react/15.4.2/react.min.js"></script>
    <script src="//cdn.jsdelivr.net/react/15.4.2/react-dom.min.js"></script>
    <script src="//cdn.jsdelivr.net/npm/graphql-query-compress@1.1.0/lib/graphql-query-compress.browser.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/graphiql@0.17.5/graphiql.min.js" integrity="sha256-fGppMKjJJ1IZ/fA9Cl8a7QJhvTHgJ1PVspgwvIfXAkY=" crossorigin="anonymous"></script>
    <script src="//unpkg.com/prettier@1.13.0/standalone.js"></script>
    <script src="//unpkg.com/prettier@1.13.0/parser-graphql.js"></script>
    <script>
        // Parse the search string to get url parameters.
        let search     = window.location.search;
        let parameters = {};

        search.substr(1).split('&').forEach(function (entry) {
            const eq = entry.indexOf('=');

            if (eq >= 0) {
                parameters[decodeURIComponent(entry.slice(0, eq))] = decodeURIComponent(entry.slice(eq + 1));
            }
        });

        // if variables was provided, try to format it.
        if (parameters.variables) {
            try {
                parameters.variables = JSON.stringify(JSON.parse(parameters.variables), null, 2);
            } catch (e) {
                // Do nothing, we want to display the invalid JSON as a string, rather
                // than present an error.
            }
        }

        // When the query and variables string is edited, update the URL bar so
        // that it can be easily shared
        function onEditQuery(newQuery) {
            parameters.query = GraphQLQueryCompress(newQuery);
            updateURL();
        }

        function onEditVariables(newVariables) {
            parameters.variables = newVariables;
            updateURL();
        }

        function onEditOperationName(newOperationName) {
            parameters.operationName = newOperationName;
            updateURL();
        }

        function updateURL() {
            let newSearch = '?' + Object.keys(parameters).filter(function (key) {
                return Boolean(parameters[key]);
            }).map(function (key) {
                return encodeURIComponent(key) + '=' + encodeURIComponent(parameters[key]);
            }).join('&');

            history.replaceState(null, null, newSearch);
        }

        // Defines a GraphQL fetcher using the fetch API. You're not required to
        // use fetch, and could instead implement graphQLFetcher however you like,
        // as long as it returns a Promise or Observable.
        function graphQLFetcher(graphQLParams) {
            const container  = document.getElementsByClassName('result-window')[0],
                  codemirror = container.getElementsByClassName('CodeMirror')[0];

            let nativeWrapper     = container.getElementsByClassName('native-wrapper'),
                fallbackRendering = false;

            if (nativeWrapper.length) {
                nativeWrapper = nativeWrapper[0];
            } else {
                nativeWrapper = document.createElement('div');

                nativeWrapper.classList.add('native-wrapper');

                container.append(nativeWrapper);
            }

            // This example expects a GraphQL server at the path /graphql.
            // Change this to point wherever you host your GraphQL server.
            return fetch('{{ route('api.web') }}', {
                    method:      'post',
                    headers:     {
                        'Accept':       'application/json',
                        'Content-Type': 'application/json',
                        'X-Chief-Team': window.TEAM.slug,
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body:        JSON.stringify(graphQLParams),
                    credentials: 'same-origin',
                },
            ).then(function (response) {
                if (window.phpdebugbar !== undefined) {
                    var datasetId = response.headers.get('phpdebugbar-id');

                    if (datasetId !== undefined) {
                        phpdebugbar.loadDataSet(datasetId, '(ajax)', undefined, true);
                    }
                }

                codemirror.style.display = 'block';

                nativeWrapper.htmlContent   = '';
                nativeWrapper.style.display = 'none';

                fallbackRendering = !response.headers.get('content-type').startsWith('application/json');

                return response.text();
            }).then(function (responseBody) {
                if (fallbackRendering) {
                    codemirror.style.display = 'none';

                    nativeWrapper.htmlContent   = responseBody;
                    nativeWrapper.style.display = 'block';

                    return 'No JSON response, falling back to native rendering!';
                }

                try {
                    return JSON.parse(responseBody);
                } catch (error) {
                    return responseBody;
                }
            });
        }

        // Render <GraphiQL /> into the body.
        // See the README in the top level of this module to learn more about
        // how you can customize GraphiQL by providing different values or
        // additional child elements.
        window.graphiql = ReactDOM.render(
            React.createElement(
                GraphiQL,
                {
                    fetcher:             graphQLFetcher,
                    query:               parameters.query,
                    variables:           parameters.variables,
                    operationName:       parameters.operationName,
                    onEditQuery:         onEditQuery,
                    onEditVariables:     onEditVariables,
                    onEditOperationName: onEditOperationName,
                    defaultQuery:        '{\n' +
                                             '  viewer {\n' +
                                             '    name\n' +
                                             '  }\n' +
                                             '}\n',
                },
                React.createElement(GraphiQL.Logo, {}),
                React.createElement(GraphiQL.Toolbar, {},
                    React.createElement(GraphiQL.Button, {
                        onClick: () => {
                            const editor      = graphiql.getQueryEditor();
                            const currentText = editor.getValue();

                            editor.setValue(
                                prettier.format(currentText, {parser: 'graphql', plugins: prettierPlugins}),
                            );
                        },
                        label:   'Pretify',
                        title:   'Prettify Query (Shif-Ctrl-P)',
                    }),
                    React.createElement(GraphiQL.Button, {
                        onClick: () => {
                            graphiql.handleToggleHistory();
                        },
                        label:   'History',
                        title:   'Show History',
                    }),
                    React.createElement(GraphiQL.Button, {
                        onClick: () => {
                            graphiql.state.schema = undefined;
                            graphiql.docExplorerComponent.reset();
                            graphiql._fetchSchema();
                        },
                        label:   'Reload Schema',
                        title:   'Reload the GraphQL schema from the server',
                    }),
                ),
            ),
            document.getElementById('graphiql'),
        );

        document.getElementsByClassName('toolbar-button')[0].click();
    </script>
@endpush

@section('body')
    @include('partial.menu', ['fullwidth' => true])

    <div class="container-fluid p-0" style="height: calc(100% - 55px);">
        <div id="graphiql">
            <div style="text-align: center; margin-top: 20px;">
                <i class="fal fa-3x fa-fw fa-circle-notch fa-spin"></i>
            </div>
        </div>
    </div>
@endsection

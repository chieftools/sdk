@extends('chief::layout.developer', ['title' => 'API documentation'])

@section('maincontent')
    <div class="flex flex-col gap-6">
        <x-tw::panel icon="fa-rectangle-api" title="Developer documentation">
            <div class="flex flex-col gap-4 text-sm text-fg-muted">
                <p>
                    Build integrations with {{ config('app.name') }} using the APIs available below. The developer guide covers product-specific concepts and workflows.
                </p>

                <div>
                    <x-tw::button :href="$developerDocumentationUrl" target="_blank" rel="noopener noreferrer" icon="fa-book-open">
                        Read the developer guide
                    </x-tw::button>
                </div>
            </div>
        </x-tw::panel>

        <x-tw::panel id="authentication" icon="fa-key" title="Authentication">
            <div class="flex flex-col gap-4 text-sm text-fg-muted">
                <p>
                    Use a team token for services, scheduled jobs, and integrations shared by a team. Personal access tokens suit private scripts and local development. Use OAuth when your integration acts on behalf of other Chief Tools users.
                </p>

                <p>
                    Token types, authorization headers, and OAuth flows are described in the shared authentication guide.
                </p>

                <div class="flex flex-wrap gap-3">
                    <x-tw::button :href="$authenticationDocumentationUrl" target="_blank" rel="noopener noreferrer" icon="fa-book-open">
                        Authentication guide
                    </x-tw::button>

                    <x-tw::button :href="$teamTokensUrl" target="_blank" rel="noopener noreferrer" type="white" icon="fa-people-group">
                        Manage team tokens
                    </x-tw::button>

                    <x-tw::button :href="$personalTokensUrl" target="_blank" rel="noopener noreferrer" type="white" icon="fa-user-key">
                        Manage personal tokens
                    </x-tw::button>
                </div>
            </div>
        </x-tw::panel>

        @if($protocols->isNotEmpty() || $mcp !== null)
            <div class="flex flex-col gap-4">
                <x-tw::panel icon="fa-server" title="APIs">
                    <ul role="list" class="-mx-5 -my-4 divide-y divide-line">
                        @foreach($protocols as $protocol)
                            <li>
                                <article class="flex items-start gap-3 px-5 py-4 sm:px-6">
                                    <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-accent text-accent-fg">
                                        <i class="fad fa-fw {{ $protocol['icon'] }}" aria-hidden="true"></i>
                                    </span>

                                    <div class="min-w-0 flex-1">
                                        <h2 class="text-lg font-medium text-fg">{{ $protocol['label'] }}</h2>
                                        <p class="mt-1 text-sm text-fg-muted">{{ $protocol['description'] }}</p>

                                        <div class="mt-4 flex flex-wrap gap-3">
                                            @if($protocol['documentation_url'])
                                                <x-tw::button :href="$protocol['documentation_url']" target="_blank" rel="noopener noreferrer" icon="fa-books">
                                                    Open documentation
                                                </x-tw::button>
                                            @endif

                                            @if($protocol['playground_url'])
                                                <x-tw::button :href="$protocol['playground_url']" icon="fa-rocket">
                                                    Open playground
                                                </x-tw::button>
                                            @endif
                                        </div>
                                    </div>
                                </article>
                            </li>
                        @endforeach

                        @if($mcp !== null)
                            <li id="mcp">
                                <article class="flex items-start gap-3 px-5 py-4 sm:px-6">
                                    <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-accent text-accent-fg">
                                        <i class="fad fa-fw fa-microchip-ai" aria-hidden="true"></i>
                                    </span>

                                    <div class="min-w-0 flex-1">
                                        <h2 class="text-lg font-medium text-fg">MCP server</h2>
                                        <p class="mt-1 text-sm text-fg-muted">Connect AI assistants and agents with the Model Context Protocol.</p>

                                        <div class="mt-4 flex flex-wrap gap-3">
                                            @if($mcp['documentation_url'])
                                                <x-tw::button :href="$mcp['documentation_url']" target="_blank" rel="noopener noreferrer" icon="fa-books">
                                                    Open documentation
                                                </x-tw::button>
                                            @endif

                                            <div class="flex min-w-0 max-w-full rounded-md shadow-xs">
                                                <code class="min-w-0 truncate rounded-l-md border border-line bg-surface-2 px-3 py-2 font-mono text-sm leading-4 text-fg" title="{{ $mcp['endpoint'] }}">{{ $mcp['endpoint'] }}</code>
                                                <button type="button"
                                                        data-clipboard="#mcp"
                                                        data-clipboard-value="{{ $mcp['endpoint'] }}"
                                                        aria-label="Copy the MCP server URL"
                                                        class="-ml-px inline-flex shrink-0 items-center rounded-r-md border border-line bg-surface-2 px-3 text-sm text-fg-muted hover:bg-surface-3 hover:text-fg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
                                                    <i class="fal fa-fw fa-copy" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            </li>
                        @endif
                    </ul>
                </x-tw::panel>

                @if($protocols->contains(fn (array $protocol): bool => filled($protocol['playground_url'])))
                    <x-tw::alert type="warning">
                        API playgrounds use production data. Queries and mutations affect your account and teams.
                    </x-tw::alert>
                @endif
            </div>
        @endif
    </div>
@endsection

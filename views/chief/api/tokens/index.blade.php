@extends('chief::layout.developer', ['title' => 'Personal access tokens'])

@section('maincontent')
    @if(session()->has('access_token'))
        <x-tw::panel icon="fa-key" :title="'New access token: ' . $user->personalAccessTokens->find(session()->get('access_token_id'))->name">
            <x-tw::alert type="warning" class="mb-3">
                This token will only be shown once! If you lose it you can disable the token and create a new one.
            </x-tw::alert>

            <x-tw::form.input name="pat" :readonly="true" :copyable="true" :value="session()->get('access_token')"/>
        </x-tw::panel>
    @endif


    <x-tw::panel icon="fa-key" title="Personal access tokens">
        <p class="text-sm mb-3">
            These tokens can be used to access the API authenticated as your account without having to create an OAuth2 client and authorize your account through that client.
        </p>

        @if($user->personalAccessTokens->isEmpty())
            <x-tw::alert type="info">
                You don't have any active personal access tokens.
            </x-tw::alert>
        @else
            <hr class="my-5">

            <ul role="list" class="-my-5 divide-y divide-gray-200">
                @foreach($user->personalAccessTokens as $token)
                    <li class="py-4">
                        <div class="flex items-center space-x-4">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">
                                    {{ $token->name }}
                                </p>
                                <p class="text-sm text-gray-500 truncate">
                                    <span data-toggle="tooltip" data-title="{{ $token->created_at->format('d-m-Y H:i:s') }}">
                                        Created
                                        <time datetime="{{ $token->created_at->toIso8601String() }}">
                                            {{ $token->created_at->diffForHumans() }}
                                        </time>
                                    </span>
                                    &middot;
                                    <span data-toggle="tooltip" data-title="{{ $token->expires_at->format('d-m-Y H:i:s') }}">
                                        Expires
                                        <time datetime="{{ $token->expires_at->toIso8601String() }}">
                                            {{ $token->expires_at->diffForHumans() }}
                                        </time>
                                    </span>
                                </p>
                            </div>
                            <div>
                                <a href="{{ route('api.tokens.delete', [$token->id]) }}"
                                   data-confirm="true"
                                   data-method="post"
                                   data-title="Disable token"
                                   data-toggle="tooltip"
                                   class="inline-flex items-center shadow-sm px-2.5 py-0.5 border border-gray-300 text-sm leading-5 font-medium rounded-full text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    <i class="fa fa-fw fa-trash-alt text-sm"></i>
                                </a>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif

        <x-slot name="footer">
            <x-tw::button :href="route('api.tokens.create')" icon="fa-plus">
                Create new token
            </x-tw::button>
        </x-slot>
    </x-tw::panel>
@endsection

@extends('chief::layout.developer', ['title' => 'Personal access tokens'])

@section('maincontent')
    <x-tw::panel icon="fa-key" title="Personal access tokens">
        <x-tw::alert>
            <x-slot name="heading">
                Personal access tokens are managed at your Chief Tools profile!
            </x-slot>

            <x-slot name="actions">
                <div class="mt-4">
                    <div class="-my-1.5 flex">
                        <x-tw::button :href="chief_base_url('/api/tokens')" type="white" size="sm" target="_blank">
                            <i class="fad fa-fw fa-external-link-square-alt"></i> Manage Tokens
                        </x-tw::button>

                        <x-tw::button :href="route('api.tokens.create')" type="white" size="sm" target="_blank" class="ml-2">
                            <i class="fad fa-fw fa-plus"></i> New Token
                        </x-tw::button>
                    </div>
                </div>
            </x-slot>
        </x-tw::alert>
    </x-tw::panel>

    @if(config('chief.auth.passport') && $user->personalAccessTokens->isNotEmpty())
        <x-tw::panel icon="fa-key" title="Deprecated personal access tokens">
            <x-tw::alert type="warning">
                These tokens are legacy tokens and are being phased out, please replace it with a new personal access token!
            </x-tw::alert>

            <ul role="list" class="-mx-5 -mb-4 divide-y divide-gray-200">
                @foreach($user->personalAccessTokens as $token)
                    <li class="py-4 px-5">
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
                                   title="Delete"
                                   data-confirm="true"
                                   data-method="post"
                                   data-title="Delete {{ $token->name }} token?"
                                   data-text="This token will stop working immediately, so make sure it's no longer in use!"
                                   data-icon="fa-trash-alt"
                                   data-color="danger"
                                   data-toggle="tooltip"
                                   class="inline-flex items-center shadow-xs px-2.5 py-0.5 border border-gray-300 text-sm leading-5 font-medium rounded-full text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    <i class="fa fa-fw fa-trash-alt text-sm"></i>
                                </a>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-tw::panel>
    @endif
@endsection

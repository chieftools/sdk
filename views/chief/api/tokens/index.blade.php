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
@endsection

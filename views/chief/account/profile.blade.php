@extends('chief::layout.account', ['title' => 'Profile'])

@section('maincontent')
    <x-tw::panel icon="fa-user" title="Profile">
        <x-tw::form.input type="text" name="name" label="Name" :readonly="true" :disabled="true" :value="auth()->user()->name"/>

        <x-tw::form.input type="email" name="email" label="Email address" :readonly="true" :disabled="true" :value="auth()->user()->email"/>

        <x-tw::form.input type="select" name="timezone" label="Timezone" :readonly="true" :disabled="true" :value="auth()->user()->timezone" :options="timezones()"/>

        <x-tw::alert class="mt-4">
            <x-slot name="heading">
                Manage your profile information at the Chief Tools dashboard!
            </x-slot>

            <x-slot name="actions">
                <div class="mt-4">
                    <div class="-mx-2 -my-1.5 flex">
                        <x-tw::button :href="chief_base_url('/settings/profile')" type="white" size="sm">
                            <i class="fad fa-fw fa-external-link-square-alt"></i> Manage Profile
                        </x-tw::button>
                    </div>
                </div>
            </x-slot>
        </x-tw::alert>
    </x-tw::panel>
@endsection

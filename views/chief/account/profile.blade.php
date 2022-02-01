@extends('chief::layout.account', ['title' => 'Profile'])

@section('maincontent')
    <x-tw::panel icon="fa-user" title="Profile">
        <x-tw::alert class="mb-4">
            <x-slot name="heading">
                Your profile is managed by Chief Tools!
            </x-slot>

            To update your info visit the Chief Tools dashboard.

            <x-slot name="actions">
                <div class="mt-4">
                    <div class="-mx-2 -my-1.5 flex">
                        <button type="button" class="bg-blue-50 px-2 py-1.5 rounded-md text-sm font-medium text-blue-800 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-blue-50 focus:ring-blue-600">
                            <i class="fad fa-fw fa-external-link-square-alt"></i> Chief Tools
                        </button>
                    </div>
                </div>
            </x-slot>
        </x-tw::alert>

        <x-tw::form.input type="text" name="name" label="Name" :readonly="true" :disabled="true" :value="auth()->user()->name"/>

        <x-tw::form.input type="email" name="email" label="Email address" :readonly="true" :disabled="true" :value="auth()->user()->email"/>

        <x-tw::form.input type="select" name="timezone" label="Timezone" :readonly="true" :disabled="true" :value="auth()->user()->timezone" :options="timezones()"/>
    </x-tw::panel>
@endsection

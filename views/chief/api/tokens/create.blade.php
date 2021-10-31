@extends('layout.default', ['title' => 'New personal access token - Developer', 'narrow' => true])

@section('content')
    <x-tw::form>
        <x-tw::panel class="mt-8" icon="fa-key" title="New personal access token">
            <x-tw::form.input type="text" name="name" label="Name" :autofocus="true" :value="$name"/>

            <x-slot name="footer">
                <x-tw::form.cancel :href="route('api.tokens')"/>
                <x-tw::form.submit>Create</x-tw::form.submit>
            </x-slot>
        </x-tw::panel>
    </x-tw::form>
@endsection

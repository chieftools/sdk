<div @class(['py-1' => config('chief.shell.variant') !== 'modern', 'border-t border-line p-1.5' => config('chief.shell.variant') === 'modern']) role="none">
    <x-chief::account.dropdown-link :href="route('api.docs.graphql')" icon="fa-plug">
        GraphQL API docs
    </x-chief::account.dropdown-link>

    <x-chief::account.dropdown-link :href="route('api.tokens')" icon="fa-key">
        Personal access tokens
    </x-chief::account.dropdown-link>
</div>

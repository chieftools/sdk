@if(($developerDocumentationLink = chief_developer_documentation_link()) !== null)
    <div @class(['py-1' => config('chief.shell.variant') !== 'modern', 'border-t border-line p-1.5' => config('chief.shell.variant') === 'modern']) role="none">
        <x-chief::account.dropdown-link :href="$developerDocumentationLink['href']" :target="$developerDocumentationLink['target']" :icon="$developerDocumentationLink['icon']">
            {{ $developerDocumentationLink['label'] }}
        </x-chief::account.dropdown-link>
    </div>
@endif

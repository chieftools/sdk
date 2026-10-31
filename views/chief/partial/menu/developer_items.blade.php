@if(($developerDocumentationLink = chief_developer_documentation_link()) !== null)
    <x-chief::account.menu-link :href="$developerDocumentationLink['href']" :target="$developerDocumentationLink['target']" :active="active('api/docs')" :text="$developerDocumentationLink['label']" :icon="$developerDocumentationLink['icon']"/>
@endif

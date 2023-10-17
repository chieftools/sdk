<div x-data="{ open: false }" x-on:click.away="open = false" class="fixed bottom-6 right-6 z-50">
    <button x-on:click="open = !open" type="button" class="bg-brand hover:bg-brand-600 text-white px-2 py-1.5 rounded-md">
        <i x-bind:class="{'opacity-0': open}" class="transition-opacity fad fa-fw fa-messages-question !absolute"></i>
        <i x-cloak x-bind:class="{'opacity-0': !open}" class="transition-opacity fa fa-fw fa-xmark !static"></i>
    </button>

    <div x-cloak x-bind:class="{'opacity-0 pointer-events-none': !open}" class="select-none transition-opacity absolute right-0 bottom-0 mt-2 mb-[44px] w-56 rounded-sm bg-white shadow-sm ring-1 ring-black ring-opacity-5 focus:outline-none" tabindex="-1">
        <div class="relative p-4">
            <h4 class="p-1.5 pt-0">Need help?</h4>
            <p class="p-1.5 pt-0 mb-1 text-xs text-muted">
                We are happy to help you with any questions you might have.
            </p>
            <a href="{{ chief_docs_url() }}?ref=support-widget-{{ config('chief.id') }}" target="_blank" rel="noopener" class="text-gray-700 hover:bg-brand hover:text-white block text-sm p-1.5 mb-1 rounded" tabindex="-1">
                <i class="fa fa-fw fa-book"></i>&nbsp;&nbsp;Documentation
            </a>
            <a href="{{ chief_roadmap_url() }}?ref=support-widget-{{ config('chief.id') }}" target="_blank" rel="noopener" class="text-gray-700 hover:bg-brand hover:text-white block text-sm p-1.5 mb-1 rounded" tabindex="-1">
                <i class="fa fa-fw fa-road"></i>&nbsp;&nbsp;Roadmap
            </a>
            <a href="{{ chief_site_url('contact') }}?ref=support-widget-{{ config('chief.id') }}" target="_blank" rel="noopener" class="text-gray-700 hover:bg-brand hover:text-white block text-sm p-1.5 rounded" tabindex="-1">
                <i class="fa fa-fw fa-comment-dots"></i>&nbsp;&nbsp;Get in touch
            </a>
        </div>
    </div>
</div>
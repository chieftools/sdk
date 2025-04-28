<div x-data="{ open: false, loadLazy: false, showBugReportBtn: false }"
     x-on:click.away="open = false"
     x-on:mouseover="loadLazy = true"
     x-init="$watch('loadLazy', () => showBugReportBtn = window.SENTRY_FEEDBACK && window.SENTRY_FEEDBACK.attachTo($refs.sentryBugReportBtn, {}) !== null)"
     class="fixed bottom-6 right-6 z-50">
    <button x-on:click="open = !open; loadLazy = true" type="button" class="bg-brand hover:bg-brand-600 text-white px-2 py-1.5 rounded-md">
        <i x-bind:class="{'opacity-0': open}" class="transition-opacity fad fa-fw fa-messages-question !absolute mt-1"></i>
        <i x-cloak x-bind:class="{'opacity-0': !open}" class="transition-opacity fad fa-fw fa-xmark !static" style="--fa-secondary-opacity: 1;"></i>
    </button>

    <div x-cloak x-bind:class="{'opacity-0 pointer-events-none': !open}" class="select-none transition-opacity absolute right-0 bottom-0 mt-2 mb-[44px] w-64 rounded-sm bg-white shadow-xs ring-1 ring-black/5 focus:outline-hidden" tabindex="-1">
        <div class="relative p-4">
            <p class="text-base p-1.5 pt-0">
                Need help?
            </p>
            <p class="p-1.5 pt-0 mb-1 text-xs text-muted">
                We are happy to help you with any questions you might have.
            </p>
            <a href="{{ chief_docs_url() }}?ref=support-widget-{{ config('chief.id') }}" target="_blank" rel="noopener" class="group text-gray-700 hover:bg-brand hover:text-white block text-sm p-1.5 mb-1 rounded" tabindex="-1">
                <i class="fad fa-fw fa-book text-brand group-hover:text-white"></i>&nbsp;&nbsp;Documentation
            </a>
            <a href="{{ chief_roadmap_url() }}?ref=support-widget-{{ config('chief.id') }}" target="_blank" rel="noopener" class="group text-gray-700 hover:bg-brand hover:text-white block text-sm p-1.5 mb-1 rounded" tabindex="-1">
                <i class="fad fa-fw fa-road text-brand group-hover:text-white"></i>&nbsp;&nbsp;Roadmap
            </a>
            <a x-show="showBugReportBtn" x-ref="sentryBugReportBtn" @click.prevent="open = false" href="#" class="group text-gray-700 hover:bg-brand hover:text-white block text-sm p-1.5 mb-1 rounded" tabindex="-1">
                <i class="fad fa-fw fa-bug text-brand group-hover:text-white"></i>&nbsp;&nbsp;Report a bug
            </a>
            <a href="{{ chief_site_url('contact') }}?ref=support-widget-{{ config('chief.id') }}" target="_blank" rel="noopener" class="group text-gray-700 hover:bg-brand hover:text-white block text-sm p-1.5 rounded" tabindex="-1">
                <i class="fad fa-fw fa-comment-dots text-brand group-hover:text-white"></i>&nbsp;&nbsp;Get in touch
            </a>

            <hr class="my-2 bg-gray-200 border-0 h-px">

            <template x-if="loadLazy">
                <iframe src="https://status.chief.tools/badge?theme=light" sandbox="allow-popups allow-top-navigation-by-user-activation" class="ml-0.5" width="250" height="30" frameborder="0" scrolling="no"></iframe>
            </template>
        </div>
    </div>
</div>

<div x-data="{ open: false, loadLazy: false, showBugReportBtn: false, statusTheme: null }"
     x-on:click.away="open = false"
     x-on:mouseover="loadLazy = true"
     x-init="$watch('loadLazy', () => {
         showBugReportBtn = window.SENTRY_FEEDBACK && window.SENTRY_FEEDBACK.attachTo($refs.sentryBugReportBtn, {}) !== null;
         statusTheme = statusTheme ?? document.documentElement.dataset.theme ?? 'light';
     })"
     class="fixed bottom-6 right-6 z-50">
    <button x-on:click="open = !open; loadLazy = true" type="button" class="bg-brand hover:bg-brand-600 text-accent-fg px-2 py-1.5 rounded-md" aria-label="Support" aria-expanded="false" aria-haspopup="true" x-bind:aria-expanded="open.toString()">
        <i x-bind:class="{'opacity-0': open}" class="transition-opacity fad fa-fw fa-messages-question !absolute mt-1"></i>
        <i x-cloak x-bind:class="{'opacity-0': !open}" class="transition-opacity fad fa-fw fa-xmark !static" style="--fa-secondary-opacity: 1;"></i>
    </button>

    <div x-cloak x-bind:class="{'opacity-0 pointer-events-none': !open}" class="select-none transition-opacity absolute right-0 bottom-0 mt-2 mb-[44px] w-64 rounded-sm bg-surface shadow-md ring-1 ring-line focus:outline-hidden" tabindex="-1">
        <div class="relative p-4">
            <p class="text-base text-fg p-1.5 pt-0">
                Need help?
            </p>
            <p class="p-1.5 pt-0 mb-1 text-xs text-muted">
                We are happy to help you with any questions you might have.
            </p>
            <a href="{{ chief_docs_url(config('chief.id') . '-support-widget') }}" target="_blank" rel="noopener" class="group text-fg-muted hover:bg-brand hover:text-accent-fg block text-sm p-1.5 mb-1 rounded" tabindex="-1">
                <i class="fad fa-fw fa-book text-brand group-hover:text-accent-fg"></i>&nbsp;&nbsp;Documentation
            </a>
            <a href="{{ chief_roadmap_url(config('chief.id') . '-support-widget') }}" target="_blank" rel="noopener" class="group text-fg-muted hover:bg-brand hover:text-accent-fg block text-sm p-1.5 mb-1 rounded" tabindex="-1">
                <i class="fad fa-fw fa-road text-brand group-hover:text-accent-fg"></i>&nbsp;&nbsp;Roadmap
            </a>
            <a x-show="showBugReportBtn" x-ref="sentryBugReportBtn" @click.prevent="open = false" href="#" class="group text-fg-muted hover:bg-brand hover:text-accent-fg block text-sm p-1.5 mb-1 rounded" tabindex="-1">
                <i class="fad fa-fw fa-bug text-brand group-hover:text-accent-fg"></i>&nbsp;&nbsp;Report a bug
            </a>
            <a href="{{ chief_site_url('contact', ref: config('chief.id') . '-support-widget') }}" target="_blank" rel="noopener" class="group text-fg-muted hover:bg-brand hover:text-accent-fg block text-sm p-1.5 rounded" tabindex="-1">
                <i class="fad fa-fw fa-comment-dots text-brand group-hover:text-accent-fg"></i>&nbsp;&nbsp;Get in touch
            </a>

            <hr class="my-2 bg-line border-0 h-px">

            <template x-if="loadLazy">
                <iframe x-bind:src="`https://status.chief.tools/badge?theme=${statusTheme}`" sandbox="allow-popups allow-top-navigation-by-user-activation" class="ml-0.5" width="250" height="30" frameborder="0" scrolling="no" style="color-scheme: none"></iframe>
            </template>
        </div>
    </div>
</div>

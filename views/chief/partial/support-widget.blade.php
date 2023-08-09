<div id="chief-support-widget" class="fixed bottom-6 right-6 z-50">
    <button id="chief-support-widget__button" type="button" class="bg-brand hover:bg-brand-600 text-white px-2 py-1.5 rounded-md">
        <i class="transition-opacity chief-support-widget__icon fad fa-fw fa-messages-question !absolute"></i>
        <i class="transition-opacity chief-support-widget__icon fa fa-fw fa-xmark !static opacity-0"></i>
    </button>

    <div id="chief-support-widget__list" class="opacity-0 pointer-events-none select-none transition-opacity absolute right-0 bottom-0 mt-2 mb-[44px] w-56 rounded-sm bg-white shadow-sm ring-1 ring-black ring-opacity-5 focus:outline-none" tabindex="-1">
        <div class="relative p-4">
            <h4 class="p-1.5 pt-0">Need help?</h4>
            <p class="p-1.5 pt-0 mb-1 text-xs text-muted">
                We are happy to help you with any questions you might have.
            </p>
            <a href="{{ chief_roadmap_url() }}?ref=support-widget-{{ config('chief.id') }}" target="_blank" class="text-gray-700 hover:bg-brand hover:text-white block text-sm p-1.5 mb-1 rounded" tabindex="-1">
                <i class="fa fa-fw fa-book"></i>&nbsp;&nbsp;Documentation
            </a>
            <a href="{{ chief_roadmap_url() }}?ref=support-widget-{{ config('chief.id') }}" target="_blank" class="text-gray-700 hover:bg-brand hover:text-white block text-sm p-1.5 mb-1 rounded" tabindex="-1">
                <i class="fa fa-fw fa-road"></i>&nbsp;&nbsp;Roadmap
            </a>
            <a href="{{ chief_site_url('contact') }}?ref=support-widget-{{ config('chief.id') }}" target="_blank" class="text-gray-700 hover:bg-brand hover:text-white block text-sm p-1.5 rounded" tabindex="-1">
                <i class="fa fa-fw fa-comment-dots"></i>&nbsp;&nbsp;Get in touch
            </a>
        </div>
    </div>
</div>
<script>
    (function () {
        const button = document.getElementById('chief-support-widget__button');
        const icons  = [...document.getElementsByClassName('chief-support-widget__icon')];
        const menu   = document.getElementById('chief-support-widget__list');

        let open = false;

        function toggle() {
            open = !open;

            menu.classList.toggle('opacity-0');
            menu.classList.toggle('pointer-events-none');
            icons.forEach(e => e.classList.toggle('opacity-0'));
        }

        button.addEventListener('click', (e) => {
            e.stopPropagation();

            toggle();
        });

        document.addEventListener('click', (e) => {
            if (!open || e.target.closest('#chief-support-widget__list')) {
                return;
            }

            toggle();
        });
    })();
</script>

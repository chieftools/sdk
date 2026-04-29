import {htmlToElement, triggerEvent} from './dom';

const modalHTML = `
<div
    class="relative z-[70]"
    x-data="{ open: false }"
    x-show="open"
    x-init="$watch('open', (o) => o === false ? $dispatch('closed') : $dispatch('opened'))"
    @open="open = true"
    @close="open = false"
    @keydown.window.escape="open = false"
>
    <div
        x-show="open"
        @click="open = false"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"
        aria-hidden="true"
    ></div>

    <div class="fixed inset-0 z-[70] w-screen overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0" @click.self="open = false">
            <div
                x-show="open"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative transform overflow-hidden rounded-lg bg-surface text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg"
            >
                <div class="bg-surface px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="hidden sm:block absolute top-0 right-0 pt-4 pr-4">
                        <button
                            type="button"
                            @click="open = false"
                            class="rounded-md bg-surface text-fg-faint hover:text-fg-muted focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-offset-surface focus:ring-brand-500 cursor-pointer"
                        >
                            <span class="sr-only">Close</span>
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    __CONTENT__
                </div>
            </div>
        </div>
    </div>
</div>
`;

export function modalDialog(options) {
    const dialog = htmlToElement(modalHTML.replace('__CONTENT__', options.content.innerHTML));

    if (options.onOpened) {
        dialog.addEventListener('opened', options.onOpened);
    }

    if (options.onClosed) {
        dialog.addEventListener('closed', options.onClosed);
    }

    dialog.addEventListener('closed', () => setTimeout(() => dialog.remove(), 1000));

    document.body.appendChild(dialog);

    window.requestAnimationFrame(() => triggerEvent(dialog, 'open'));

    return dialog;
}

document.querySelectorAll('[data-modal]').forEach(node => {
    node.addEventListener('click', e => {
        e.preventDefault();

        const content = document.querySelector(node.getAttribute('data-modal'));

        modalDialog({content});
    });
});

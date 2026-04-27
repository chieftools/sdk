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
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
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
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto shrink-0 flex items-center justify-center h-12 w-12 rounded-full __ICON_BG__ sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fad fa-fw __ICON__"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-fg">__TITLE__</h3>
                            <div class="mt-2">
                                <p class="text-sm text-fg-subtle">__TEXT__</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-surface-2 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button
                        type="button"
                        class="btn-confirm w-full inline-flex justify-center rounded-sm border border-transparent shadow-xs px-4 py-2 text-base font-medium focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-offset-surface sm:ml-3 sm:w-auto sm:text-sm cursor-pointer __CONFIRM_BTN_CLASS__"
                        @click="$dispatch('confirm')"
                    >
                        __CONFIRM__
                    </button>
                    <button
                        type="button"
                        class="btn-cancel mt-3 w-full inline-flex justify-center rounded-sm border border-line shadow-xs px-4 py-2 bg-surface-2 text-base font-medium text-fg hover:bg-surface-3 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-offset-surface focus:ring-fg-subtle sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm cursor-pointer"
                        @click="open = false"
                    >
                        __CANCEL__
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
`;

const typeToClassesMap = Object.freeze({
    success: ['bg-green/15', 'text-green', 'bg-green hover:bg-green/85 focus:ring-green text-white'],
    warning: ['bg-amber/15', 'text-amber', 'bg-amber hover:bg-amber/85 focus:ring-amber text-white'],
    danger: ['bg-red/15', 'text-red', 'bg-red hover:bg-red/85 focus:ring-red text-white'],
    brand: ['bg-accent-soft', 'text-accent', 'bg-brand-600 hover:bg-brand-700 focus:ring-brand-500 text-accent-fg'],
    info: ['bg-blue/15', 'text-blue', 'bg-blue hover:bg-blue/85 focus:ring-blue text-white'],
});

function classesForConfirmButton(color) {
    if (typeToClassesMap[color]) {
        return typeToClassesMap[color][2];
    }

    return typeToClassesMap.info[2];
}

function classForIconBackground(color) {
    if (typeToClassesMap[color]) {
        return typeToClassesMap[color][0];
    }

    return typeToClassesMap.info[0];
}

function classForIcon(color) {
    if (typeToClassesMap[color]) {
        return typeToClassesMap[color][1];
    }

    return typeToClassesMap.info[1];
}

export function confirmDialog(options) {
    const dialog = htmlToElement(
        modalHTML
            .replace('__ICON__', `${options.icon || 'fa-exclamation-circle'} ${classForIcon(options.color)}`)
            .replace('__TEXT__', options.text)
            .replace('__TITLE__', options.title)
            .replace('__ICON_BG__', classForIconBackground(options.color))
            .replace('__CONFIRM_BTN_CLASS__', classesForConfirmButton(options.color))
            .replace('__CONFIRM__', options.confirm || 'Ok')
            .replace('__CANCEL__', options.cancel || 'Cancel')
    );

    if (options.withoutCancel) {
        dialog.getElementsByClassName('btn-cancel')[0].remove();
    }

    if (options.onOpened) {
        dialog.addEventListener('opened', options.onOpened);
    }

    if (options.onClosed) {
        dialog.addEventListener('closed', options.onClosed);
    }

    dialog.addEventListener('confirm', () => {
        if (options.onConfirm) {
            options.onConfirm();
        }

        if (options.closeOnConfirm) {
            triggerEvent(dialog, 'close');
        }
    });

    dialog.addEventListener('closed', () => setTimeout(() => dialog.remove(), 1000));

    document.body.appendChild(dialog);

    window.requestAnimationFrame(() => triggerEvent(dialog, 'open'));
}

document.querySelectorAll('[data-confirm]').forEach(node => {
    const shouldConfirm = node.getAttribute('data-confirm') === 'true';

    node.addEventListener('click', e => {
        e.preventDefault();

        if (node.attributes.getNamedItem('disabled') !== null) {
            return;
        }

        const form = document.createElement('form');

        form.action = node.getAttribute('href');
        form.method = 'post';

        const method = node.getAttribute('data-method');

        if (method && method.toLowerCase() !== 'post') {
            const methodInput = document.createElement('input');

            methodInput.name = '_method';
            methodInput.type = 'hidden';
            methodInput.value = method;

            form.appendChild(methodInput);
        }

        const csrf = document.createElement('input');

        csrf.name = '_token';
        csrf.type = 'hidden';
        csrf.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        form.appendChild(csrf);

        document.body.appendChild(form);

        if (!shouldConfirm) {
            form.submit();

            return;
        }

        confirmDialog({
            icon: node.getAttribute('data-icon') || 'fa-exclamation-circle',
            text: node.getAttribute('data-text') || 'This action cannot be undone.',
            title: node.getAttribute('data-title') || 'Are you sure?',
            color: node.getAttribute('data-color') || 'info',
            cancel: node.getAttribute('data-cancel-text') || 'No',
            confirm: node.getAttribute('data-confirm-text') || 'Yes',
            onConfirm: () => form.submit(),
            onClosed: () => form.remove(),
        });
    });
});

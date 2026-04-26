import {htmlToElement, triggerEvent} from './dom';

import modalHTML from './confirm.html?raw';

const typeToClassesMap = Object.freeze({
    success: ['bg-green-100', 'text-success', 'bg-green-600 hover:bg-green-700 focus:ring-green-500'],
    warning: ['bg-orange-100', 'text-warning', 'bg-orange-600 hover:bg-orange-700 focus:ring-orange-500'],
    danger: ['bg-red-100', 'text-danger', 'bg-red-600 hover:bg-red-700 focus:ring-red-500'],
    brand: ['bg-brand-100', 'text-brand-600', 'bg-brand-600 hover:bg-brand-700 focus:ring-brand-500'],
    info: ['bg-blue-100', 'text-blue-600', 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500'],
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

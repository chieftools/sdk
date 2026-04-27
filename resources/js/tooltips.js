import tippy from 'tippy.js';

export function initializeTooltips(el = document) {
    el.querySelectorAll('[data-toggle="tooltip"]').forEach(node => {
        tippy(node, {
            content: node.getAttribute('data-title') || node.getAttribute('title'),
            placement: node.getAttribute('data-placement') || 'top',
            onMount(instance) {
                const content = instance.popper.querySelector('.tippy-content');

                if (content !== null) {
                    content.style.whiteSpace = 'pre-line';
                }
            },
        });

        node.removeAttribute('title');
    });
}

initializeTooltips();

window.initializeTooltips = initializeTooltips;

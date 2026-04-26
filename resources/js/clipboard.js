import tippy from 'tippy.js';
import ClipboardJS from 'clipboard';

const tooltipText = 'Copied!';
const tooltipTimeout = 1000;

export function initializeClipboard(el = document) {
    el.querySelectorAll('[data-clipboard]').forEach(node => {
        const targetSelector = node.getAttribute('data-clipboard');
        const options = {};

        if (node.hasAttribute('data-clipboard-value')) {
            options.text = () => node.getAttribute('data-clipboard-value');
        } else {
            options.target = () => document.querySelector(targetSelector);
        }

        const clipboard = new ClipboardJS(node, options);

        const tooltip = tippy(node, {
            content: tooltipText,
            trigger: 'manual',
        });

        clipboard.on('success', () => {
            tooltip.show();

            setTimeout(() => {
                tooltip.hide();
            }, tooltipTimeout);
        });
    });
}

initializeClipboard();

import tippy from 'tippy.js';
import ClipboardJS from 'clipboard';

const tooltipText = 'Copied!';
const tooltipTimeout = 1000;

export function initializeClipboard(el = document) {
    el.querySelectorAll('[data-clipboard]').forEach(node => {
        // Livewire initializes every element with a wire: attribute, which reaches the same node more than once
        if (node._clipboard) {
            return;
        }

        const targetSelector = node.getAttribute('data-clipboard');
        const options = {};

        if (node.hasAttribute('data-clipboard-value')) {
            options.text = () => node.getAttribute('data-clipboard-value');
        } else {
            options.target = () => document.querySelector(targetSelector);
        }

        const clipboard = new ClipboardJS(node, options);

        node._clipboard = clipboard;

        // The confirmation is anchored to a detached element placed over the node, which keeps `node._tippy` free for a hover tooltip
        const tooltip = tippy(document.createElement('div'), {
            content: tooltipText,
            trigger: 'manual',
            appendTo: () => document.body,
            getReferenceClientRect: () => node.getBoundingClientRect(),
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

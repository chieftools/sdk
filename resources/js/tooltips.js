import tippy, {delegate} from 'tippy.js';

const selector = '[data-toggle="tooltip"]';

// Moves a native title into data-title so the browser does not show its own tooltip next to ours
function tooltipContent(reference) {
    const title = reference.getAttribute('title');

    if (title !== null) {
        reference.removeAttribute('title');

        if (!reference.hasAttribute('data-title')) {
            reference.setAttribute('data-title', title);
        }
    }

    return reference.getAttribute('data-title') || '';
}

const tooltipProps = {
    content: reference => tooltipContent(reference),
    onShow(instance) {
        // Read the content again on every show, a Livewire morph can change it after the tooltip was created
        const content = tooltipContent(instance.reference);

        if (content === '') {
            return false;
        }

        const placement = instance.reference.getAttribute('data-placement') || 'top';

        if (instance.props.placement !== placement) {
            instance.setProps({placement});
        }

        instance.setContent(content);
    },
    onMount(instance) {
        const content = instance.popper.querySelector('.tippy-content');

        if (content !== null) {
            content.style.whiteSpace = 'pre-line';
        }
    },
};

let isDelegated = false;

/**
 * Tooltips are created when an element is first hovered or focused, so a page with many tooltips does not pay for
 * them upfront and elements added later (Livewire updates, wire:navigate) need no initialization. The listener is on
 * the root element because wire:navigate replaces the body.
 */
function delegateTooltips() {
    if (isDelegated) {
        return;
    }

    delegate(document.documentElement, {...tooltipProps, target: selector});

    isDelegated = true;
}

/**
 * Creates the tooltips within an element right away. This is only needed when code uses `_tippy` on an element
 * before it was hovered, elements that already have a tooltip are skipped.
 */
export function initializeTooltips(el = document) {
    delegateTooltips();

    el.querySelectorAll(selector).forEach(node => {
        if (node._tippy) {
            return;
        }

        tippy(node, {...tooltipProps, placement: node.getAttribute('data-placement') || 'top'});
    });
}

delegateTooltips();

window.initializeTooltips = initializeTooltips;

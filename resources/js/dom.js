export function triggerEvent(element, event) {
    element.dispatchEvent(new CustomEvent(event));
}

export function htmlToElement(html) {
    const template = document.createElement('template');

    template.innerHTML = html.trim();

    return template.content.firstChild;
}

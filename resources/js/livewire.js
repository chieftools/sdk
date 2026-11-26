import {initializeClipboard} from './clipboard';

// Tooltips need no initialization here, they are delegated from the root element (see tooltips.js)
document.addEventListener('livewire:init', () => {
    const initComponents = ({el}) => {
        initializeClipboard(el);
    };

    Livewire.hook('element.init', initComponents);
    Livewire.hook('morphed', initComponents);
});

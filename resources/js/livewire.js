import {initializeTooltips} from './tooltips';
import {initializeClipboard} from './clipboard';

document.addEventListener('livewire:init', () => {
    const initComponents = ({el}) => {
        initializeTooltips(el);
        initializeClipboard(el);
    };

    Livewire.hook('element.init', initComponents);
    Livewire.hook('morphed', initComponents);
});

let registrationBridgeRegistered = false;
const registeredAlpineInstances = new WeakSet();
const shellMenuCenteredClasses = ['md:absolute', 'md:inset-y-0', 'md:left-1/2', 'md:w-max', 'md:-translate-x-1/2', 'md:flex-none', 'md:justify-center-safe'];

function normalize(value) {
    return String(value || '')
        .toLowerCase()
        .replace(/\s+/g, ' ')
        .trim();
}

function commandParts(query) {
    const parts = String(query || '')
        .split('>')
        .map(part => part.trim());
    const scoped = parts.length > 1;

    return {
        scoped,
        scope: scoped ? parts.slice(0, -1).filter(Boolean).join(' > ') : '',
        term: scoped ? parts.at(-1) : query,
    };
}

function currentAlpine() {
    if (typeof window === 'undefined') {
        return null;
    }

    return window.Alpine || null;
}

function registerChiefShellBridge() {
    if (registrationBridgeRegistered || typeof document === 'undefined') {
        return;
    }

    document.addEventListener('alpine:init', () => registerChiefShell(currentAlpine()));
    document.addEventListener('livewire:navigating', () => registerChiefShell(currentAlpine()));

    registrationBridgeRegistered = true;
}

function chiefShell() {
    return {
        menuOpen: false,
        accountOpen: false,
        teamOpen: false,
        themeOpen: false,
        paletteOpen: false,
        paletteQuery: '',
        activeIndex: 0,
        remoteResults: [],
        remoteLoading: false,
        remoteError: false,
        remoteSearchUrl: null,
        remoteSearchTimer: null,
        remoteSearchAbort: null,
        themeUpdateUrl: null,
        theme: 'light',
        systemThemeQuery: null,
        systemThemeHandler: null,
        shellElement: null,
        shellChromeObserver: null,
        shellResizeHandler: null,
        shellLayoutFrame: null,
        shellMenuCentered: false,
        menuScrolledFromStart: false,
        menuScrolledToEnd: false,

        init() {
            this.shellElement = this.$el;
            this.remoteSearchUrl = this.shellElement.dataset.commandPaletteSearchUrl || null;
            this.themeUpdateUrl = this.shellElement.dataset.themeUpdateUrl || null;
            this.theme = this.shellElement.dataset.themePreference || this.shellElement.dataset.theme || 'light';
            this.systemThemeQuery = window.matchMedia('(prefers-color-scheme: dark)');
            this.systemThemeHandler = () => {
                if (this.theme === 'system') {
                    this.applyTheme();
                }
            };
            this.systemThemeQuery.addEventListener?.('change', this.systemThemeHandler);
            this.applyTheme();
            this.$nextTick(() => this.watchShellChrome());
        },

        destroy() {
            this.systemThemeQuery?.removeEventListener?.('change', this.systemThemeHandler);
            this.shellChromeObserver?.disconnect();

            if (this.shellResizeHandler) {
                window.removeEventListener('resize', this.shellResizeHandler);
            }

            if (this.shellLayoutFrame) {
                cancelAnimationFrame(this.shellLayoutFrame);
            }
        },

        resolvedTheme() {
            if (this.theme === 'system') {
                return this.systemThemeQuery?.matches ? 'dark' : 'light';
            }

            return this.theme === 'dark' ? 'dark' : 'light';
        },

        applyTheme() {
            const theme = this.resolvedTheme();
            const shellElement = this.shellElement || this.$root || this.$el;
            const rootElement = document.documentElement;

            shellElement.dataset.theme = theme;
            shellElement.dataset.themePreference = this.theme;
            shellElement.classList.toggle('dark', theme === 'dark');
            shellElement.style.colorScheme = theme;

            rootElement.dataset.theme = theme;
            rootElement.dataset.themePreference = this.theme;
            rootElement.classList.toggle('dark', theme === 'dark');
            rootElement.style.colorScheme = theme;
        },

        setTheme(theme) {
            this.theme = ['light', 'dark', 'system'].includes(theme) ? theme : 'light';
            this.applyTheme();
            this.persistTheme();
        },

        persistTheme() {
            if (!this.themeUpdateUrl) {
                return;
            }

            const url = this.themeUpdateUrl.replace('__theme__', encodeURIComponent(this.theme));

            fetch(url, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            }).catch(() => {});
        },

        themeButtonClasses(theme) {
            if (this.theme === theme) {
                return 'bg-surface-raised text-fg shadow-sm';
            }

            return 'text-fg-subtle hover:text-fg';
        },

        watchShellChrome() {
            const elements = [this.$refs.shellHeader, this.$refs.shellLeft, this.$refs.shellActions].filter(Boolean);

            if (!elements.length || typeof ResizeObserver === 'undefined') {
                this.scheduleShellMenuLayout();
                return;
            }

            this.shellResizeHandler = () => this.scheduleShellMenuLayout();
            this.shellChromeObserver = new ResizeObserver(this.shellResizeHandler);
            elements.forEach(element => this.shellChromeObserver.observe(element));
            window.addEventListener('resize', this.shellResizeHandler, {passive: true});
            document.fonts?.ready?.then(() => this.scheduleShellMenuLayout());
            this.scheduleShellMenuLayout();
        },

        scheduleShellMenuLayout() {
            if (this.shellLayoutFrame) {
                return;
            }

            this.shellLayoutFrame = requestAnimationFrame(() => {
                this.shellLayoutFrame = null;
                this.updateShellMenuLayout();
                this.$nextTick(() => this.updateShellMenuScrollState());
            });
        },

        updateShellMenuLayout() {
            const header = this.$refs.shellHeader;
            const left = this.$refs.shellLeft;
            const menu = this.$refs.shellMenu;
            const right = this.$refs.shellActions;

            if (!header || !left || !menu || !right) {
                this.shellMenuCentered = false;
                return;
            }

            if (menu.dataset.shellMenuAutoCenter !== 'true') {
                this.shellMenuCentered = false;
                this.applyShellMenuCentering(false);
                return;
            }

            const headerRect = header.getBoundingClientRect();
            const leftRect = left.getBoundingClientRect();
            const rightRect = right.getBoundingClientRect();
            const center = headerRect.left + headerRect.width / 2;
            const gutter = 16;
            const centeredWidth = Math.max(0, Math.min(center - leftRect.right - gutter, rightRect.left - center - gutter) * 2);
            const menuItemsWidth = Array.from(menu.children).reduce((width, item) => width + item.getBoundingClientRect().width, 0);

            this.shellMenuCentered = menuItemsWidth <= Math.floor(centeredWidth);
            this.applyShellMenuCentering(this.shellMenuCentered);
        },

        applyShellMenuCentering(centered) {
            const menu = this.$refs.shellMenu;

            if (!menu) {
                return;
            }

            shellMenuCenteredClasses.forEach(className => menu.classList.toggle(className, centered));
        },

        updateShellMenuScrollState() {
            const menu = this.$refs.shellMenu;

            if (!menu) {
                this.menuScrolledFromStart = false;
                this.menuScrolledToEnd = false;
                return;
            }

            this.menuScrolledFromStart = menu.scrollLeft > 0;
            this.menuScrolledToEnd = Math.ceil(menu.scrollLeft + menu.clientWidth) < menu.scrollWidth;
        },

        openPalette(query = '') {
            this.paletteQuery = query;
            this.activeIndex = 0;
            this.menuOpen = false;
            this.accountOpen = false;
            this.teamOpen = false;
            this.themeOpen = false;
            this.paletteOpen = true;
            this.$nextTick(() => {
                this.$refs.paletteSearch?.focus();
                this.$refs.paletteSearch?.setSelectionRange(this.paletteQuery.length, this.paletteQuery.length);
                this.syncPaletteActive();
                this.queueRemoteSearch();
            });
        },

        closePalette() {
            this.paletteOpen = false;
            this.activeIndex = 0;
            this.remoteSearchAbort?.abort();
            this.remoteLoading = false;
        },

        togglePalette(mode = 'empty') {
            const query = mode === 'switcher' ? 'Chief Tools > ' : '';

            // Mode is inferred from the current query so that the filter
            // chips and manual typing stay consistent with the keyboard
            // shortcuts. Cmd+K means "I want the empty palette"; if we're
            // already there, close. If we're in switcher mode, swap to empty
            // without closing. Same logic mirrored for Cmd+J / 'switcher'.
            if (this.paletteOpen) {
                const currentMode = this.paletteQuery.startsWith('Chief Tools > ') ? 'switcher' : 'empty';

                if (currentMode === mode) {
                    this.closePalette();
                    return;
                }

                this.paletteQuery = query;
                this.activeIndex = 0;
                this.$nextTick(() => {
                    this.$refs.paletteSearch?.focus();
                    this.$refs.paletteSearch?.setSelectionRange(query.length, query.length);
                    this.syncPaletteActive();
                    this.queueRemoteSearch();
                });

                return;
            }

            this.openPalette(query);
        },

        closePanels() {
            this.accountOpen = false;
            this.teamOpen = false;
            this.themeOpen = false;
            this.closePalette();
        },

        normalize(value) {
            return normalize(value);
        },

        fuzzy(value, query) {
            const haystack = normalize(value);
            const needle = normalize(query);

            if (!needle) {
                return true;
            }

            let position = 0;

            for (const character of needle) {
                position = haystack.indexOf(character, position);

                if (position === -1) {
                    return false;
                }

                position++;
            }

            return true;
        },

        commandParts(query) {
            return commandParts(query);
        },

        commandScore(query, title, category, body = '') {
            const parts = commandParts(query);

            if (parts.scoped && !this.fuzzy(category, parts.scope)) {
                return 0;
            }

            const heading = normalize(title);
            const details = normalize(body);
            const categoryText = normalize(category);
            const term = normalize(parts.term);
            const combined = `${categoryText} ${heading} ${details}`;

            if (!term) {
                return parts.scoped ? 20 : 10;
            }

            if (heading === term || categoryText === term) {
                return 1000;
            }

            if (heading.startsWith(term)) {
                return 950 - heading.length;
            }

            if (heading.split(' ').some(word => word.startsWith(term))) {
                return 900 - heading.indexOf(term);
            }

            if (heading.includes(term)) {
                return 750 - heading.indexOf(term);
            }

            if (this.fuzzy(heading, term)) {
                return 550 - Math.max(0, heading.length - term.length);
            }

            if (term.length <= 2) {
                if (details.split(' ').some(word => word.startsWith(term))) {
                    return 250 - details.indexOf(term);
                }

                if (details.includes(term)) {
                    return 100 - details.indexOf(term);
                }

                return 0;
            }

            if (details.split(' ').some(word => word.startsWith(term))) {
                return 450 - details.indexOf(term);
            }

            if (details.includes(term)) {
                return 300 - details.indexOf(term);
            }

            if (combined.includes(term)) {
                return 250 - combined.indexOf(term);
            }

            if (this.fuzzy(details, term)) {
                return 100 - Math.max(0, details.length - term.length);
            }

            if (this.fuzzy(combined, term)) {
                return 50 - Math.max(0, combined.length - term.length);
            }

            return 0;
        },

        commandOrder(query, title, category, body = '') {
            const score = this.commandScore(query, title, category, body);

            return score > 0 ? 10000 - score : 10000;
        },

        matchesCommand(query, title, category, body = '') {
            return this.commandScore(query, title, category, body) > 0;
        },

        paletteQueryChanged() {
            this.activeIndex = 0;
            this.queueRemoteSearch();
            this.$nextTick(() => this.syncPaletteActive());
        },

        setPaletteQuery(query) {
            this.paletteQuery = query;
            this.paletteQueryChanged();
            this.$nextTick(() => this.$refs.paletteSearch?.focus());
        },

        shouldSearchRemote() {
            if (!this.remoteSearchUrl) {
                return false;
            }

            const parts = commandParts(this.paletteQuery);
            const term = normalize(parts.term);

            return term.length >= (parts.scoped ? 1 : 2);
        },

        queueRemoteSearch() {
            clearTimeout(this.remoteSearchTimer);

            if (!this.shouldSearchRemote()) {
                this.remoteSearchAbort?.abort();
                this.remoteResults = [];
                this.remoteLoading = false;
                this.remoteError = false;
                return;
            }

            this.remoteSearchTimer = setTimeout(() => this.fetchRemoteSearch(), 150);
        },

        fetchRemoteSearch() {
            if (!this.shouldSearchRemote()) {
                return;
            }

            this.remoteSearchAbort?.abort();
            this.remoteSearchAbort = new AbortController();
            this.remoteLoading = true;
            this.remoteError = false;

            const url = new URL(this.remoteSearchUrl, window.location.origin);
            url.searchParams.set('q', this.paletteQuery);
            url.searchParams.set('limit', '8');

            fetch(url, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                signal: this.remoteSearchAbort.signal,
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Command palette search failed');
                    }

                    return response.json();
                })
                .then(payload => {
                    this.remoteResults = Array.isArray(payload.data) ? payload.data : [];
                    this.remoteLoading = false;
                    this.$nextTick(() => this.syncPaletteActive());
                })
                .catch(error => {
                    if (error.name === 'AbortError') {
                        return;
                    }

                    this.remoteResults = [];
                    this.remoteLoading = false;
                    this.remoteError = true;
                    this.$nextTick(() => this.syncPaletteActive());
                });
        },

        visiblePaletteItems() {
            return Array.from(this.$refs.paletteItems?.querySelectorAll('[data-shell-command]') || [])
                .filter(item => item.offsetParent !== null)
                .sort((a, b) => Number(a.style.order || 10000) - Number(b.style.order || 10000));
        },

        hasPaletteItems() {
            return this.visiblePaletteItems().length > 0;
        },

        hasPaletteSearchTerm() {
            return normalize(commandParts(this.paletteQuery).term).length > 0;
        },

        syncPaletteActive() {
            const items = this.visiblePaletteItems();

            if (items.length === 0) {
                this.activeIndex = 0;
                return;
            }

            this.activeIndex = Math.max(0, Math.min(this.activeIndex, items.length - 1));

            items.forEach((item, index) => {
                if (index === this.activeIndex) {
                    item.setAttribute('data-active', 'true');
                    item.scrollIntoView({block: 'nearest'});
                } else {
                    item.removeAttribute('data-active');
                }
            });
        },

        movePalette(delta) {
            const items = this.visiblePaletteItems();

            if (items.length === 0) {
                return;
            }

            this.activeIndex = (this.activeIndex + delta + items.length) % items.length;
            this.syncPaletteActive();
        },

        activatePalette() {
            this.visiblePaletteItems()[this.activeIndex]?.click();
        },
    };
}

export function registerChiefShell(Alpine = currentAlpine()) {
    registerChiefShellBridge();

    if (!Alpine || registeredAlpineInstances.has(Alpine)) {
        return false;
    }

    Alpine.data('chiefShell', chiefShell);
    registeredAlpineInstances.add(Alpine);

    return true;
}

export {chiefShell};

const registeredAlpineInstances = new WeakSet();

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

function chiefShell() {
    return {
        menuOpen: false,
        accountOpen: false,
        teamOpen: false,
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
        },

        destroy() {
            this.systemThemeQuery?.removeEventListener?.('change', this.systemThemeHandler);
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

            shellElement.dataset.theme = theme;
            shellElement.dataset.themePreference = this.theme;
            shellElement.classList.toggle('dark', theme === 'dark');
            shellElement.style.colorScheme = theme;
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
                return 'bg-white text-gray-950 shadow-sm dark:bg-gray-900 dark:text-gray-100 dark:shadow-black/30';
            }

            return 'text-gray-500 hover:text-gray-950 dark:text-gray-400 dark:hover:text-gray-100';
        },

        openPalette(query = '') {
            this.paletteQuery = query;
            this.activeIndex = 0;
            this.menuOpen = false;
            this.accountOpen = false;
            this.teamOpen = false;
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

        closePanels() {
            this.accountOpen = false;
            this.teamOpen = false;
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

export function registerChiefShell(Alpine) {
    if (!Alpine || registeredAlpineInstances.has(Alpine)) {
        return;
    }

    Alpine.data('chiefShell', chiefShell);
    registeredAlpineInstances.add(Alpine);
}

export {chiefShell};

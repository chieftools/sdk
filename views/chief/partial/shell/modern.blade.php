@php
    $minimalMenu = isset($minimalMenu) && $minimalMenu;
    $fullwidthMenu = $minimalMenu || (isset($fullwidthMenu) && $fullwidthMenu);
    $menuItems = collect($menuItems ?? [])->values();
    $themePreference = ChiefTools\SDK\Chief::themePreference();
    $theme = ChiefTools\SDK\Chief::theme();
    $tool = array_merge([
        'id' => config('chief.id', 'chief'),
        'name' => config('app.title', config('app.name', 'Chief Tools')),
        'short' => config('app.name', config('app.title', 'Chief')),
        'icon' => config('chief.brand.icon', 'fa-toolbox'),
        'color' => config('chief.brand.color', '#34495e'),
        'logoUrl' => config('chief.brand.logoUrl'),
        'logoColorUrl' => config('chief.brand.logoColorUrl', config('chief.brand.logoUrl')),
        'href' => $logoRedirect ?? home(),
    ], $shellTool ?? []);

    $toolLogoUrl = $tool['logoColorUrl'] ?? $tool['logoUrl'] ?? null;

    $commandKey = static fn (?string $category, ?string $label): string => strtolower(trim((string)$category)) . '|' . strtolower(trim((string)$label));
    $menuCommandKeys = $menuItems
        ->map(static fn (array $item): string => $commandKey($tool['name'], $item['text'] ?? $item['label'] ?? ''))
        ->filter(static fn (string $key): bool => !str_ends_with($key, '|'))
        ->values()
        ->all();

    $appsSource = collect($shellApps ?? []);

    if ($appsSource->isEmpty() && config('chief.shell.app_switcher') && function_exists('chief_apps')) {
        $appsSource = rescue(fn () => chief_apps(null) ?? collect(), collect(), false);
    }

    $teamHint = 'current';

    if (auth()->check()) {
        $currentTeam = rescue(fn () => auth()->user()?->team, null, false);
        $teamHint = $currentTeam?->slug ?? $currentTeam?->id ?? 'current';
    }

    $resolveAppUrl = static function ($app) use ($teamHint): string {
        $template = data_get($app, 'url_template') ?? data_get($app, 'urlTemplate');

        if (!empty($template)) {
            return str_replace(['{team}', '{team_id}', '{teamId}', ':team'], (string)$teamHint, (string)$template);
        }

        return data_get($app, 'href') ?? data_get($app, 'url') ?? data_get($app, 'baseUrl') ?? data_get($app, 'base_url') ?? '#';
    };

    $apps = $appsSource
        ->map(static function ($app) use ($resolveAppUrl): array {
            $description = data_get($app, 'short_description') ?? data_get($app, 'shortDescription') ?? data_get($app, 'tagline') ?? data_get($app, 'description') ?? '';

            if ($description instanceof \Illuminate\Support\HtmlString) {
                $description = (string)$description;
            }

            return [
                'id' => data_get($app, 'id') ?? data_get($app, 'slug') ?? data_get($app, 'name'),
                'name' => data_get($app, 'name') ?? data_get($app, 'id') ?? 'Chief Tool',
                'short' => data_get($app, 'short') ?? data_get($app, 'name') ?? data_get($app, 'id') ?? 'Tool',
                'icon' => data_get($app, 'icon') ?? 'fa-toolbox',
                'color' => data_get($app, 'color') ?? '#34495e',
                'href' => $resolveAppUrl($app),
                'logoUrl' => data_get($app, 'logo.white') ?? data_get($app, 'logoUrl') ?? data_get($app, 'logo.color') ?? null,
                'description' => strip_tags((string)$description),
                'badge' => data_get($app, 'badge'),
                'target' => data_get($app, 'target'),
            ];
        })
        ->filter(fn (array $app): bool => !empty($app['id']) && !empty($app['name']))
        ->values();

    $commands = collect($shellCommands ?? [])
        ->map(static fn (array $command): array => [
            'label' => $command['label'] ?? $command['text'] ?? '',
            'href' => $command['href'] ?? '#',
            'icon' => $command['icon'] ?? 'fa-arrow-right',
            'category' => $command['category'] ?? $tool['name'],
            'description' => $command['description'] ?? $command['group'] ?? '',
            'target' => $command['target'] ?? null,
            'wire' => $command['wire'] ?? false,
        ])
        ->filter(fn (array $command): bool => !empty($command['label']))
        ->reject(static fn (array $command): bool => in_array($commandKey($command['category'], $command['label']), $menuCommandKeys, true))
        ->unique(static fn (array $command): string => $commandKey($command['category'], $command['label']))
        ->values();

    $activeMenuItem = $menuItems->first(fn (array $item): bool => $item['active'] ?? false);
    $mobileTitle = $activeMenuItem['text'] ?? $activeMenuItem['label'] ?? $tool['name'];
    $mobileIcon = $activeMenuItem['icon'] ?? $tool['icon'];
    $allAppsUrl = function_exists('chief_base_url') ? chief_base_url(ref: config('chief.id') . '-app-switcher') : '#';
    $commandPaletteSearchUrl = \Illuminate\Support\Facades\Route::has('chief.shell.commands.search')
        ? route('chief.shell.commands.search', [], false)
        : null;
    $themeUpdateUrl = \Illuminate\Support\Facades\Route::has('chief.shell.theme')
        ? route('chief.shell.theme', ['theme' => '__theme__'], false)
        : null;
@endphp

<nav data-chief-shell
     data-theme="{{ $theme }}"
     data-theme-preference="{{ $themePreference }}"
     data-tool="{{ $tool['id'] }}"
     @if($commandPaletteSearchUrl) data-command-palette-search-url="{{ $commandPaletteSearchUrl }}" @endif
     @if($themeUpdateUrl) data-theme-update-url="{{ $themeUpdateUrl }}" @endif
     @class([
        'group/shell relative z-40 bg-surface text-fg',
        'dark' => $theme === 'dark',
     ])
     x-data="chiefShell"
     x-on:keydown.window.escape="menuOpen = false; closePanels()"
     x-on:keydown.window="if (($event.metaKey || $event.ctrlKey) && $event.key.toLowerCase() === 'k') { $event.preventDefault(); togglePalette('empty'); } else if (({{ config('chief.shell.app_switcher') ? 'true' : 'false' }}) && ($event.metaKey || $event.ctrlKey) && $event.key.toLowerCase() === 'j') { $event.preventDefault(); togglePalette('switcher'); }"
>
    @if($themePreference === 'system')
        <script>
            (function (shell) {
                try {
                    var dark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    shell.classList.toggle('dark', dark);
                    shell.dataset.theme = dark ? 'dark' : 'light';
                    shell.style.colorScheme = dark ? 'dark' : 'light';
                } catch (error) {}
            })(document.currentScript.parentElement);
        </script>
    @endif

    <header class="border-b border-line bg-surface">
        <div @class([
            'relative flex h-[52px] items-stretch gap-2 px-2 sm:px-6 lg:px-8',
            'container mx-auto max-w-7xl' => !$fullwidthMenu,
            'w-full' => $fullwidthMenu,
        ])>
            <button type="button"
                    class="grid size-9 cursor-pointer place-items-center self-center rounded-md text-fg-subtle transition hover:bg-surface-2 hover:text-fg md:hidden"
                    x-on:click="menuOpen = !menuOpen; accountOpen = false; teamOpen = false; closePalette()"
                    aria-controls="chief-shell-mobile-menu"
                    x-bind:aria-expanded="menuOpen.toString()">
                <span class="sr-only">Toggle main menu</span>
                <i class="fa fa-fw fa-bars" x-show="!menuOpen"></i>
                <i class="fa fa-fw fa-xmark" x-show="menuOpen"></i>
            </button>

            <div class="relative flex items-center">
                <a href="{{ $tool['href'] }}"
                   @class([
                       'text flex h-10 items-center gap-2 px-1 pr-2 text-fg transition hover:bg-surface-2',
                       'rounded-l-lg' => config('chief.shell.app_switcher') && config('chief.shell.command_palette'),
                       'rounded-lg' => !(config('chief.shell.app_switcher') && config('chief.shell.command_palette')),
                   ])>
                    <span class="grid size-9 shrink-0 place-items-center rounded-md text-accent">
                        @if(!empty($toolLogoUrl))
                            <img src="{{ $toolLogoUrl }}" alt="" class="max-h-8 max-w-8">
                        @else
                            <i class="fad fa-fw {{ $tool['icon'] }} text-2xl"></i>
                        @endif
                    </span>
                    <span class="hidden max-w-48 truncate text-sm font-semibold md:block">{{ $tool['name'] }}</span>
                </a>
                @if(config('chief.shell.app_switcher') && config('chief.shell.command_palette'))
                    <button type="button"
                            class="flex h-10 cursor-pointer items-center rounded-r-lg px-1.5 text-fg transition hover:bg-surface-2"
                            x-on:click="togglePalette('switcher')"
                            x-bind:aria-expanded="paletteOpen.toString()"
                            aria-haspopup="dialog"
                            aria-label="Switch app"
                            data-toggle="tooltip"
                            data-title="Switch app (⌘J)">
                        <i class="fa fa-fw fa-chevron-down text-[10px] text-fg-faint"></i>
                    </button>
                @endif
            </div>

            @unless($menuItems->isEmpty())
                <div class="hidden w-px self-center bg-line md:block"></div>

                <div
                    x-data="{
                        scrolledFromStart: false,
                        scrolledToEnd: false,
                        updateScrollState() {
                            this.scrolledFromStart = this.$el.scrollLeft > 0;
                            this.scrolledToEnd = Math.ceil(this.$el.scrollLeft + this.$el.clientWidth) < this.$el.scrollWidth;
                        },
                    }"
                    x-init="
                        updateScrollState();
                        const observer = new ResizeObserver(() => updateScrollState());
                        observer.observe($el);
                        for (const child of $el.children) observer.observe(child);
                    "
                    x-on:scroll.passive="updateScrollState()"
                    :style="{
                        '--mask-start': scrolledFromStart ? '1.5rem' : '0px',
                        '--mask-end': scrolledToEnd ? 'calc(100% - 1.5rem)' : '100%',
                    }"
                    @class([
                        'hidden min-w-0 flex-1 items-stretch overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden [mask-image:linear-gradient(to_right,transparent_0,black_var(--mask-start,0px),black_var(--mask-end,100%),transparent_100%)] md:flex',
                        'md:justify-center-safe' => $fullwidthMenu,
                    ])
                >
                    @foreach($menuItems as $item)
                        @include('chief::partial.shell.nav-item', ['item' => $item])
                    @endforeach
                </div>
            @else
                <div class="flex-1"></div>
            @endunless

            <div class="flex flex-1 items-center gap-1 md:hidden">
                <i class="fa-fw {{ $mobileIcon }} text-sm text-accent"></i>
                <span class="min-w-0 truncate text-sm font-medium text-fg">{{ $mobileTitle }}</span>
            </div>

            <div class="flex items-center gap-1 self-center">
                @if(config('chief.shell.command_palette'))
                    <button type="button"
                            class="hidden h-8 min-w-52 cursor-pointer items-center gap-2 rounded-md border border-line bg-surface-2 px-2 text-xs text-fg-subtle transition hover:border-line-strong hover:bg-surface-3 hover:text-fg-muted lg:flex"
                            x-on:click="openPalette()">
                        <i class="fa fa-fw fa-search"></i>
                        <span class="flex-1 text-left">Search or jump to...</span>
                        <span class="rounded border border-line bg-surface px-1.5 py-0.5 font-mono text-[10px] font-medium text-fg-subtle">Cmd K</span>
                    </button>

                    <button type="button"
                            class="grid size-8 cursor-pointer place-items-center rounded-md bg-surface-2 text-fg-faint transition hover:bg-surface-3 hover:text-fg-muted lg:hidden"
                            x-on:click="openPalette()">
                        <span class="sr-only">Search</span>
                        <i class="fad fa-fw fa-search text-sm"></i>
                    </button>
                @endif

                @auth
                    @if(config('chief.teams') && auth()->user()->team)
                        <div class="relative">
                            <button type="button"
                                    class="relative grid size-9 cursor-pointer place-items-center rounded-lg transition hover:bg-surface-2"
                                    x-on:click="teamOpen = !teamOpen; accountOpen = false; menuOpen = false; closePalette()"
                                    x-bind:aria-expanded="teamOpen.toString()"
                                    aria-haspopup="menu">
                                <span class="sr-only">Open team menu</span>
                                <img class="size-8 rounded-md" src="{{ auth()->user()->team->avatar_url }}" alt="">
                                <span class="absolute bottom-0 right-0 grid size-4 place-items-center rounded-sm border-2 border-surface bg-accent text-[7px] font-bold leading-none text-accent-fg">T</span>
                            </button>

                            <div x-cloak
                                 x-show="teamOpen"
                                 x-on:click.away="teamOpen = false"
                                 x-transition.origin.top.right
                                 class="absolute right-0 top-full z-50 mt-2 w-80 overflow-hidden rounded-lg border border-line bg-surface shadow-xl"
                                 role="menu">
                                @include('chief::partial.team.dropdown_items')
                            </div>
                        </div>
                    @endif

                    <div class="relative">
                        <button type="button"
                                class="relative grid size-9 cursor-pointer place-items-center rounded-lg transition hover:bg-surface-2"
                                x-on:click="accountOpen = !accountOpen; teamOpen = false; menuOpen = false; closePalette()"
                                x-bind:aria-expanded="accountOpen.toString()"
                                aria-haspopup="menu">
                            <span class="sr-only">Open account menu</span>
                            <img class="size-8 rounded-md" src="{{ auth()->user()->avatar_url }}" alt="">
                            @if(config('chief.teams') && auth()->user()->team)
                                <span class="absolute bottom-0 right-0 grid size-4 place-items-center rounded-sm border-2 border-surface bg-accent text-[7px] font-bold leading-none text-accent-fg">P</span>
                            @endif
                        </button>

                        <div x-cloak
                             x-show="accountOpen"
                             x-on:click.away="accountOpen = false"
                             x-transition.origin.top.right
                             class="absolute right-0 top-full z-50 mt-2 w-80 overflow-hidden rounded-lg border border-line bg-surface shadow-xl"
                             role="menu">
                            <div class="flex items-center gap-3 px-4 py-3">
                                <img class="size-9 rounded-lg" src="{{ auth()->user()->avatar_url }}" alt="">
                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-sm font-medium text-fg">{{ auth()->user() }}</div>
                                    <div class="truncate text-xs text-fg-subtle">{{ auth()->user()->email }}</div>
                                </div>
                            </div>

                            @if(config('chief.shell.theme_selector', true))
                                <div class="border-t border-line px-3 py-2.5" role="none">
                                    <div class="flex gap-1 rounded-md bg-surface-2 p-1">
                                        <button type="button"
                                                class="flex flex-1 cursor-pointer items-center justify-center gap-1 rounded px-2 py-1 text-xs font-medium transition"
                                                x-bind:class="themeButtonClasses('light')"
                                                x-on:click="setTheme('light')">
                                            <i class="fad fa-fw fa-sun-bright text-[11px]"></i>
                                            <span>Light</span>
                                        </button>
                                        <button type="button"
                                                class="flex flex-1 cursor-pointer items-center justify-center gap-1 rounded px-2 py-1 text-xs font-medium transition"
                                                x-bind:class="themeButtonClasses('dark')"
                                                x-on:click="setTheme('dark')">
                                            <i class="fad fa-fw fa-moon text-[11px]"></i>
                                            <span>Dark</span>
                                        </button>
                                        <button type="button"
                                                class="flex flex-1 cursor-pointer items-center justify-center gap-1 rounded px-2 py-1 text-xs font-medium transition"
                                                x-bind:class="themeButtonClasses('system')"
                                                x-on:click="setTheme('system')">
                                            <i class="fad fa-fw fa-display text-[11px]"></i>
                                            <span>System</span>
                                        </button>
                                    </div>
                                </div>
                            @endif

                            @include('chief::partial.account.dropdown_items')
                        </div>
                    </div>
                @endauth
            </div>
        </div>
    </header>

    <div x-cloak
         x-show="menuOpen"
         id="chief-shell-mobile-menu"
         class="fixed inset-0 top-[55px] z-50 bg-black/40 backdrop-blur-sm md:hidden"
         x-on:click="menuOpen = false">
        <div class="h-full w-72 border-r border-line bg-surface shadow-xl" x-on:click.stop x-transition>
            <div class="p-2">
                @foreach($menuItems as $item)
                    @php
                        $active = $item['active'] ?? false;
                        $href = $item['href'] ?? '#';
                        $icon = $item['icon'] ?? null;
                    @endphp
                    <a href="{{ $href }}"
                       @class([
                           'group flex min-h-12 items-center gap-3 rounded-md px-3 py-3 text-base font-medium transition',
                           'bg-surface-2 text-fg' => $active,
                           'text-fg-muted hover:bg-surface-2 hover:text-fg' => !$active,
                       ])
                       @if(!empty($item['wire'])) wire:navigate @endif
                       x-on:click="menuOpen = false">
                        @if($icon)
                            <i @class([
                                'fa-fw text-base',
                                $icon,
                                'text-accent' => $active,
                                'text-fg-faint group-hover:text-fg-muted' => !$active,
                            ])></i>
                        @endif
                        <span class="truncate">{{ $item['text'] ?? $item['label'] ?? '' }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    @if(config('chief.shell.command_palette'))
        <div x-cloak
             x-show="paletteOpen"
             x-transition:enter="ease-out duration-150"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-100"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[60] bg-black/40 p-4 pt-[12vh] backdrop-blur-sm"
             x-on:click="closePalette()">
            <div class="mx-auto max-w-xl overflow-hidden rounded-xl border border-line bg-surface shadow-2xl"
                 x-on:click.stop
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 -translate-y-2">
                <div class="flex items-center gap-3 border-b border-line px-4 py-3">
                    <i class="fa fa-fw text-fg-faint" x-bind:class="remoteLoading ? 'fa-spinner-third fa-spin' : 'fa-search'"></i>
                    <input x-ref="paletteSearch"
                           x-model="paletteQuery"
                           x-on:input="paletteQueryChanged()"
                           x-on:keydown.arrow-down.prevent="movePalette(1)"
                           x-on:keydown.arrow-up.prevent="movePalette(-1)"
                           x-on:keydown.enter.prevent="activatePalette()"
                           type="search"
                           class="min-w-0 flex-1 border-0 bg-transparent p-0 text-sm text-fg placeholder:text-fg-faint focus:ring-0">
                    <span class="rounded border border-line bg-surface-2 px-1.5 py-0.5 font-mono text-[10px] font-medium text-fg-subtle">esc</span>
                </div>

                <div class="flex gap-2 border-b border-line bg-surface-2 px-3 py-2 text-xs text-fg-subtle">
                    <button type="button" class="cursor-pointer rounded-md px-2 py-1 font-medium hover:bg-surface hover:text-fg" x-on:click="setPaletteQuery('')">
                        All
                    </button>
                    <button type="button" class="cursor-pointer rounded-md px-2 py-1 font-medium hover:bg-surface hover:text-fg" x-on:click="setPaletteQuery(@js($tool['name'] . ' > '))">
                        {{ $tool['name'] }}
                    </button>
                    @if($apps->isNotEmpty())
                        <button type="button" class="cursor-pointer rounded-md px-2 py-1 font-medium hover:bg-surface hover:text-fg" x-on:click="setPaletteQuery(@js('Chief Tools > '))">
                            Chief Tools
                        </button>
                    @endif
                </div>

                <div x-ref="paletteItems" class="flex max-h-96 flex-col overflow-y-auto p-1.5">
                    @foreach($menuItems as $item)
                        @php($label = $item['text'] ?? $item['label'] ?? '')
                        <a href="{{ $item['href'] ?? '#' }}"
                           class="group flex items-center gap-3 rounded-md px-3 py-2 text-fg-muted transition hover:bg-surface-2 hover:text-fg data-[active=true]:bg-surface-2 data-[active=true]:text-fg"
                           data-shell-command
                           data-shell-category="{{ $tool['name'] }}"
                           data-shell-title="{{ strtolower($label) }}"
                           data-shell-body="{{ strtolower($tool['name']) }}"
                           x-show="matchesCommand(paletteQuery, $el.dataset.shellTitle, $el.dataset.shellCategory, $el.dataset.shellBody)"
                           x-bind:style="{ order: commandOrder(paletteQuery, $el.dataset.shellTitle, $el.dataset.shellCategory, $el.dataset.shellBody) }"
                           x-on:mouseenter="activeIndex = visiblePaletteItems().indexOf($el); syncPaletteActive()"
                           @if(!empty($item['wire'])) wire:navigate @endif
                           x-on:click="closePalette()">
                            @if(!empty($item['icon']))
                                <span class="grid size-7 place-items-center rounded-md bg-surface-2 text-fg-subtle">
                                    <i class="fa-fw {{ $item['icon'] }} text-sm"></i>
                                </span>
                            @endif
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium">{{ $label }}</span>
                                <span class="block truncate text-xs text-fg-subtle">{{ $tool['name'] }} &gt; {{ $label }}</span>
                            </span>
                            <i class="fa fa-fw fa-chevron-right text-xs text-fg-faint group-hover:text-fg-subtle"></i>
                        </a>
                    @endforeach

                    @foreach($commands as $command)
                        <a href="{{ $command['href'] }}"
                           class="group flex items-center gap-3 rounded-md px-3 py-2 text-fg-muted transition hover:bg-surface-2 hover:text-fg data-[active=true]:bg-surface-2 data-[active=true]:text-fg"
                           data-shell-command
                           data-shell-category="{{ $command['category'] }}"
                           data-shell-title="{{ strtolower($command['label']) }}"
                           data-shell-body="{{ strtolower($command['description']) }}"
                           x-show="matchesCommand(paletteQuery, $el.dataset.shellTitle, $el.dataset.shellCategory, $el.dataset.shellBody)"
                           x-bind:style="{ order: commandOrder(paletteQuery, $el.dataset.shellTitle, $el.dataset.shellCategory, $el.dataset.shellBody) }"
                           x-on:mouseenter="activeIndex = visiblePaletteItems().indexOf($el); syncPaletteActive()"
                           @if($command['target']) target="{{ $command['target'] }}" @endif
                           @if($command['target'] === '_blank') rel="noopener" @endif
                           @if($command['wire']) wire:navigate @endif
                           x-on:click="closePalette()">
                            <span class="grid size-7 place-items-center rounded-md bg-surface-2 text-fg-subtle">
                                <i class="fa-fw {{ $command['icon'] }} text-sm"></i>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium">{{ $command['label'] }}</span>
                                <span class="block truncate text-xs text-fg-subtle">{{ $command['category'] }} &gt; {{ $command['description'] ?: $command['label'] }}</span>
                            </span>
                            <i class="fa fa-fw fa-chevron-right text-xs text-fg-faint group-hover:text-fg-subtle"></i>
                        </a>
                    @endforeach

                    @foreach($apps as $app)
                        <a href="{{ $app['href'] }}"
                           class="group flex items-center gap-3 rounded-md px-3 py-2 text-fg-muted transition hover:bg-surface-2 hover:text-fg data-[active=true]:bg-surface-2 data-[active=true]:text-fg"
                           data-shell-command
                           data-shell-category="Chief Tools"
                           data-shell-title="{{ strtolower($app['name'] . ' ' . $app['short']) }}"
                           data-shell-body="{{ strtolower($app['description']) }}"
                           x-show="matchesCommand(paletteQuery, $el.dataset.shellTitle, $el.dataset.shellCategory, $el.dataset.shellBody)"
                           x-bind:style="{ order: commandOrder(paletteQuery, $el.dataset.shellTitle, $el.dataset.shellCategory, $el.dataset.shellBody) }"
                           x-on:mouseenter="activeIndex = visiblePaletteItems().indexOf($el); syncPaletteActive()"
                           @if($app['target']) target="{{ $app['target'] }}" @endif
                           @if($app['target'] === '_blank') rel="noopener" @endif
                           x-on:click="closePalette()">
                            <span class="grid size-8 place-items-center rounded-md text-white" style="background-color: {{ $app['color'] }};">
                                @if($app['logoUrl'])
                                    <img src="{{ $app['logoUrl'] }}" alt="" class="max-h-6 max-w-6">
                                @else
                                    <i class="fad fa-fw {{ $app['icon'] }} text-sm"></i>
                                @endif
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium">Open {{ $app['name'] }}</span>
                                @if($app['description'])
                                    <span class="block truncate text-xs text-fg-subtle">Chief Tools &gt; {{ $app['name'] }} · {{ $app['description'] }}</span>
                                @else
                                    <span class="block truncate text-xs text-fg-subtle">Chief Tools &gt; {{ $app['name'] }}</span>
                                @endif
                            </span>
                            <i class="fa fa-fw fa-arrow-up-right text-xs text-fg-faint group-hover:text-fg-subtle"></i>
                        </a>
                    @endforeach

                    <template x-for="result in remoteResults" x-bind:key="result.id">
                        <a x-bind:href="result.url || '#'"
                           class="group flex items-center gap-3 rounded-md px-3 py-2 text-fg-muted transition hover:bg-surface-2 hover:text-fg data-[active=true]:bg-surface-2 data-[active=true]:text-fg"
                           data-shell-command
                           x-bind:data-shell-category="result.category || ''"
                           x-bind:data-shell-title="result.title || ''"
                           x-bind:data-shell-body="result.description || ''"
                           x-bind:style="{ order: result.order || 9500 }"
                           x-bind:target="result.target || null"
                           x-bind:rel="result.target === '_blank' ? 'noopener' : null"
                           x-on:mouseenter="activeIndex = visiblePaletteItems().indexOf($el); syncPaletteActive()"
                           x-on:click="closePalette()">
                            <span class="relative grid size-7 place-items-center overflow-hidden rounded-md bg-surface-2 text-fg-subtle">
                                <i class="fa-fw text-sm" x-bind:class="result.icon || 'fad fa-arrow-right'"></i>
                                <img x-show="result.icon_url"
                                     x-bind:src="result.icon_url"
                                     alt=""
                                     loading="lazy"
                                     class="absolute hidden size-5 rounded-sm"
                                     x-on:load="$el.classList.remove('hidden'); $el.previousElementSibling?.classList.add('hidden')"
                                     x-on:error="$el.remove()">
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium" x-text="result.title"></span>
                                <span class="block truncate text-xs text-fg-subtle">
                                    <span x-text="result.subtitle || result.category"></span>
                                    <template x-if="result.description">
                                        <span> · <span x-text="result.description"></span></span>
                                    </template>
                                </span>
                            </span>
                            <i class="fa fa-fw fa-chevron-right text-xs text-fg-faint group-hover:text-fg-subtle"></i>
                        </a>
                    </template>

                    <div x-cloak x-show="remoteError" class="rounded-md px-3 py-2 text-xs text-red">
                        Search results could not be loaded.
                    </div>

                    <div x-cloak
                         x-show="hasPaletteSearchTerm() && !remoteLoading && !remoteError && !hasPaletteItems()"
                         class="rounded-md px-3 py-6 text-center text-sm text-fg-subtle">
                        No results found.
                    </div>
                </div>

                <div class="flex items-center gap-4 border-t border-line bg-surface-2 px-4 py-2 text-xs text-fg-subtle">
                    <span class="flex items-center gap-1">
                        <span class="rounded border border-line bg-surface px-1.5 py-0.5 font-mono text-[10px]">↑</span>
                        <span class="rounded border border-line bg-surface px-1.5 py-0.5 font-mono text-[10px]">↓</span> move
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="rounded border border-line bg-surface px-1.5 py-0.5 font-mono text-[10px]">Enter</span> open
                    </span>
                    @if($apps->isNotEmpty())
                        <a href="{{ $allAppsUrl }}" target="_blank" rel="noopener" class="ml-auto inline-flex items-center gap-1 font-medium text-fg-muted hover:text-fg">
                            <span>All apps</span>
                            <i class="fa fa-fw fa-arrow-up-right-from-square text-[10px] text-fg-faint"></i>
                        </a>
                    @else
                        <span class="ml-auto">Use &gt; to scope results</span>
                    @endif
                </div>
            </div>
        </div>
    @endif
</nav>

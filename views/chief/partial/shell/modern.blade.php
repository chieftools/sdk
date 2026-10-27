@php
    // The sidebar layout moves the menu, with room for groups and nested items, and what the header holds on larger
    // screens into a column on the left. Apps opt in with `chief.shell.layout`; the minimal menu of marketing pages, and
    // visitors who are not signed in (see `Chief::shellLayout()`), always get the top bar
    $minimalMenu = isset($minimalMenu) && $minimalMenu;
    $sidebarLayout = !$minimalMenu && ChiefTools\SDK\Chief::shellLayout() === 'sidebar';
    // Choosing the layout only applies to the app's own pages
    $layoutSwitchable = !$minimalMenu && config('chief.shell.layout_switcher') && auth()->check() && \Illuminate\Support\Facades\Route::has('account.preferences.toggle');
    $fullwidthMenu = $sidebarLayout || $minimalMenu || (isset($fullwidthMenu) && $fullwidthMenu);
    $menuItems = collect($menuItems ?? [])->values();
    // Pinned items, such as settings, sit at the bottom of the sidebar and the drawer, apart from the work itself
    $mainMenuItems = $menuItems->reject(static fn (array $item): bool => (bool)($item['pinned'] ?? false))->values();
    $pinnedMenuItems = $menuItems->filter(static fn (array $item): bool => (bool)($item['pinned'] ?? false))->values();
    // Nested items are commands too, under their parent's name and with its icon unless they have their own
    $commandMenuItems = $menuItems->flatMap(static fn (array $item): array => [
        $item,
        ...collect($item['children'] ?? [])
            ->map(static fn (array $child): array => [...$child, 'parent' => $item['text'] ?? $item['label'] ?? null, 'icon' => $child['icon'] ?? $item['icon'] ?? null])
            ->all(),
    ])->filter(static fn (array $item): bool => !empty($item['href']))->values();
    $themePreference = ChiefTools\SDK\Chief::themePreference();
    $theme = ChiefTools\SDK\Chief::theme();
    $tool = array_merge([
        'id' => config('chief.id', 'chief'),
        'name' => config('app.title', config('app.name', 'Chief Tools')),
        'href' => $logoRedirect ?? home(),
        'icon' => config('chief.brand.icon'),
        'brandIcon' => config('chief.brand.brandIcon'),
        'color' => config('chief.brand.color'),
        'short' => config('app.name', config('app.title', 'Chief')),
        'logoUrl' => config('chief.brand.logoUrl'),
        'logoColorUrl' => config('chief.brand.logoColorUrl', config('chief.brand.logoUrl')),
    ], $shellTool ?? []);

    $toolLogoUrl = $tool['logoColorUrl'] ?? $tool['logoUrl'] ?? null;

    $commandKey = static fn (?string $category, ?string $label): string => strtolower(trim((string)$category)) . '|' . strtolower(trim((string)$label));
    $menuCommandKeys = $commandMenuItems
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
                'brandIcon' => data_get($app, 'brandIcon') ?? data_get($app, 'brand_icon'),
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

    $normalizeCommand = static fn (array $command): array => [
            'label' => $command['label'] ?? $command['text'] ?? '',
            'href' => $command['href'] ?? '#',
            'icon' => $command['icon'] ?? 'fa-arrow-right',
            'category' => $command['category'] ?? $tool['name'],
            'description' => $command['description'] ?? $command['group'] ?? '',
            'keywords' => collect(\Illuminate\Support\Arr::wrap($command['keywords'] ?? []))
                ->filter(static fn (mixed $keyword): bool => is_string($keyword) && $keyword !== '')
                ->implode(' '),
            'target' => $command['target'] ?? null,
            'wire' => $command['wire'] ?? false,
        ];

    $commands = collect($shellCommands ?? [])
        ->map($normalizeCommand)
        ->filter(fn (array $command): bool => !empty($command['label']))
        ->reject(static fn (array $command): bool => in_array($commandKey($command['category'], $command['label']), $menuCommandKeys, true))
        ->unique(static fn (array $command): string => $commandKey($command['category'], $command['label']))
        ->values();
    $commandContextLabel = trim((string) data_get($shellCommandContext ?? [], 'label', ''));
    $contextCommands = collect(data_get($shellCommandContext ?? [], 'commands', []))
        ->filter(static fn (mixed $command): bool => is_array($command))
        ->map($normalizeCommand)
        ->filter(static fn (array $command): bool => !empty($command['label']))
        ->unique(static fn (array $command): string => $commandKey($command['category'], $command['label']))
        ->values();
    $themeCommands = collect([
        [
            'theme' => 'light',
            'label' => __('chief::ui.shell.switch_to_light_theme'),
            'icon' => 'fad fa-sun-bright',
            'description' => __('chief::ui.shell.use_light_appearance'),
        ],
        [
            'theme' => 'dark',
            'label' => __('chief::ui.shell.switch_to_dark_theme'),
            'icon' => 'fad fa-moon',
            'description' => __('chief::ui.shell.use_dark_appearance'),
        ],
        [
            'theme' => 'system',
            'label' => __('chief::ui.shell.use_system_theme'),
            'icon' => 'fad fa-display',
            'description' => __('chief::ui.shell.follow_device_appearance'),
        ],
    ]);

    // Menu items that need attention (a `danger` badge, such as firing alerts) are hidden in the drawer on small
    // screens, so the button that opens it shows a dot; screen readers hear what the badges say
    $menuAttention = $menuItems
        ->flatMap(static fn (array $item): array => [$item, ...($item['children'] ?? [])])
        ->filter(static fn (array $item): bool => ($item['badge_tone'] ?? null) === 'danger' && !in_array($item['badge'] ?? null, [null, '', 0], true))
        ->map(static fn (array $item): string => $item['badge_label'] ?? ($item['text'] ?? $item['label'] ?? '') . ': ' . $item['badge'])
        ->values();

    // Where users choose the layout, the palette offers the other one, below everything else
    $layoutCommand = $layoutSwitchable
        ? ($sidebarLayout
            ? ['layout' => 'topbar', 'label' => __('chief::ui.shell.switch_to_top_bar_layout'), 'icon' => 'fad fa-browser', 'description' => __('chief::ui.shell.show_menu_in_top_bar')]
            : ['layout' => 'sidebar', 'label' => __('chief::ui.shell.switch_to_sidebar_layout'), 'icon' => 'fad fa-sidebar', 'description' => __('chief::ui.shell.show_menu_in_sidebar')])
        : null;
    $allAppsUrl = function_exists('chief_base_url') ? chief_base_url(ref: config('chief.id') . '-app-switcher') : '#';
    $hasDynamicCommandProviders = collect(config('chief.shell.command_palette_providers', []))
        ->filter()
        ->isNotEmpty();
    $commandPaletteSearchUrl = $hasDynamicCommandProviders && auth()->check() && \Illuminate\Support\Facades\Route::has('chief.shell.commands.search')
        ? route('chief.shell.commands.search', [], false)
        : null;
    $themeUpdateUrl = \Illuminate\Support\Facades\Route::has('chief.shell.theme')
        ? route('chief.shell.theme', ['theme' => '__theme__'], false)
        : null;
@endphp

<nav data-chief-shell
     data-shell-layout="{{ $sidebarLayout ? 'sidebar' : 'topbar' }}"
     data-theme="{{ $theme }}"
     data-theme-preference="{{ $themePreference }}"
     data-tool="{{ $tool['id'] }}"
     @if($commandPaletteSearchUrl) data-command-palette-search-url="{{ $commandPaletteSearchUrl }}" @endif
     @if($themeUpdateUrl) data-theme-update-url="{{ $themeUpdateUrl }}" @endif
     @if($layoutSwitchable) data-layout-update-url="{{ route('account.preferences.toggle', [], false) }}" data-layout-preference="{{ ChiefTools\SDK\Chief::SIDEBAR_LAYOUT_PREFERENCE }}" @endif
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

    {{-- The sidebar layout has everything in the header in its sidebar from the lg breakpoint --}}
    <header @class(['border-b border-line bg-surface', 'lg:hidden' => $sidebarLayout])>
        <div x-ref="shellHeader" @class([
            'relative flex h-[52px] items-stretch gap-2 px-2 sm:px-6',
            'lg:px-8',
            'container mx-auto max-w-7xl' => !$fullwidthMenu,
            'w-full' => $fullwidthMenu,
        ])>
            {{--
                The way into the menu on small screens, so as clear as the other controls and a comfortable tap target. It
                hangs into the gutter so its icon lines up with the icons of the page's breadcrumbs and title
            --}}
            <button type="button"
                    @class([
                        'relative -ml-[5px] grid size-10 shrink-0 cursor-pointer place-items-center self-center rounded-md text-fg-muted transition hover:bg-surface-2 hover:text-fg',
                        'md:hidden' => !$sidebarLayout,
                        'lg:hidden' => $sidebarLayout,
                    ])
                    x-ref="mobileMenuButton"
                    x-bind:class="menuOpen && 'text-fg!'"
                    x-on:click="menuOpen = !menuOpen; accountOpen = false; teamOpen = false; themeOpen = false; closePalette(); if (menuOpen && $event.detail === 0) focusFirstMenuItem($refs.mobileMenu)"
                    aria-controls="chief-shell-mobile-menu"
                    x-bind:aria-expanded="menuOpen.toString()">
                <span class="sr-only">{{ __('chief::ui.shell.toggle_main_menu') }}@if($menuAttention->isNotEmpty()), {{ $menuAttention->implode(', ') }}@endif</span>
                <i class="fa fa-fw fa-bars" x-show="!menuOpen"></i>
                <i class="fa fa-fw fa-xmark" x-cloak x-show="menuOpen"></i>
                @if($menuAttention->isNotEmpty())
                    <span class="absolute top-2 right-2 size-2 rounded-full bg-red-500 ring-2 ring-surface dark:bg-red-400" x-show="!menuOpen" aria-hidden="true"></span>
                @endif
            </button>

            {{-- The logo's padding and hover area hang into the gutter, so the logo lines up with the page content, like the account menu on the right --}}
            <div x-ref="shellLeft" class="flex items-stretch gap-2 md:-ml-2">
                @include('chief::partial.shell.logo')

                @unless($menuItems->isEmpty() || $sidebarLayout)
                    <div class="hidden w-px self-center bg-line md:block"></div>
                @endunless
            </div>

            @if($menuItems->isNotEmpty() && !$sidebarLayout)
                <div
                    x-ref="shellMenu"
                    data-shell-menu-auto-center="{{ $fullwidthMenu ? 'true' : 'false' }}"
                    x-on:scroll.passive="updateShellMenuScrollState()"
                    :style="{
                        '--mask-start': menuScrolledFromStart ? '1.5rem' : '0px',
                        '--mask-end': menuScrolledToEnd ? 'calc(100% - 1.5rem)' : '100%',
                    }"
                    @class([
                        'hidden min-w-0 flex-1 items-stretch overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden [mask-image:linear-gradient(to_right,transparent_0,black_var(--mask-start,0px),black_var(--mask-end,100%),transparent_100%)] md:flex',
                        'md:absolute md:inset-y-0 md:left-1/2 md:w-max md:-translate-x-1/2 md:flex-none md:justify-center-safe' => $fullwidthMenu,
                    ])
                >
                    @foreach($menuItems as $item)
                        @include('chief::partial.shell.nav-item', ['item' => $item])
                    @endforeach
                </div>
            @else
                <div class="flex-1"></div>
            @endif

            {{-- Where the menu is in the drawer, keeps the actions on the right --}}
            <div @class(['flex-1', 'md:hidden' => !$sidebarLayout, 'lg:hidden' => $sidebarLayout])></div>

            <div x-ref="shellActions" @class([
                'flex items-center gap-1 self-center',
                'md:ml-auto' => $fullwidthMenu,
            ])>
                @if(config('chief.shell.command_palette'))
                    <button type="button"
                            class="hidden h-8 min-w-52 cursor-pointer items-center gap-2 rounded-md border border-line bg-surface-2 px-2 text-xs text-fg-subtle transition hover:border-line-strong hover:bg-surface-3 hover:text-fg-muted lg:flex"
                            x-on:click="openPalette()">
                        <i class="fa fa-fw fa-search"></i>
                        <span class="flex-1 text-left">{{ __('chief::ui.shell.search_or_jump') }}</span>
                        <span class="rounded border border-line bg-surface px-1.5 py-0.5 font-mono text-[10px] font-medium text-fg-subtle">Cmd K</span>
                    </button>

                    <button type="button"
                            class="group/search grid size-9 cursor-pointer place-items-center rounded-lg lg:hidden"
                            x-on:click="openPalette()">
                        <span class="sr-only">{{ __('chief::ui.shell.search') }}</span>
                        <span class="grid size-8 place-items-center rounded-md border border-line bg-surface-2 text-fg-subtle transition group-hover/search:border-line-strong group-hover/search:bg-surface-3 group-hover/search:text-fg-muted">
                            <i class="fad fa-fw fa-search text-sm"></i>
                        </span>
                    </button>
                @endif

                @stack('chief.shell.actions')

                @guest
                    @if(config('chief.shell.theme_selector', true) && $themeUpdateUrl)
                        <div class="relative">
                            <button type="button"
                                    class="relative grid size-9 cursor-pointer place-items-center rounded-lg text-fg-subtle transition hover:bg-surface-2 hover:text-fg"
                                    x-on:click="themeOpen = !themeOpen; menuOpen = false; accountOpen = false; teamOpen = false; closePalette()"
                                    x-bind:aria-expanded="themeOpen.toString()"
                                    aria-haspopup="menu"
                                    aria-label="{{ __('chief::ui.shell.theme') }}">
                                <i class="fad fa-fw text-sm" x-bind:class="resolvedTheme() === 'dark' ? 'fa-moon' : 'fa-sun-bright'"></i>
                            </button>

                            <div x-cloak
                                 x-show="themeOpen"
                                 x-on:click.away="if ($el.offsetParent !== null) themeOpen = false"
                                 x-transition.origin.top.right
                                 class="absolute right-0 top-full z-50 mt-2 w-80 overflow-hidden rounded-lg border border-line bg-surface shadow-xl"
                                 role="menu"
                                 aria-label="{{ __('chief::ui.shell.theme') }}">
                                <div class="p-1.5" role="none">
                                    <button type="button"
                                            class="flex w-full cursor-pointer items-center gap-2.5 rounded-md px-2.5 py-2 text-left text-sm font-medium text-fg-muted transition hover:bg-surface-2 hover:text-fg"
                                            x-bind:class="{ 'bg-surface-2 text-fg': theme === 'light' }"
                                            x-on:click="setTheme('light'); themeOpen = false">
                                        <span class="grid size-6 shrink-0 place-items-center rounded-md bg-surface-2 text-fg-subtle">
                                            <i class="fad fa-fw fa-sun-bright text-[11px]"></i>
                                        </span>
                                        <span class="min-w-0 flex-1 truncate">{{ __('chief::ui.shell.light') }}</span>
                                        <i x-show="theme === 'light'" class="fa fa-fw fa-check text-xs text-accent"></i>
                                    </button>
                                    <button type="button"
                                            class="flex w-full cursor-pointer items-center gap-2.5 rounded-md px-2.5 py-2 text-left text-sm font-medium text-fg-muted transition hover:bg-surface-2 hover:text-fg"
                                            x-bind:class="{ 'bg-surface-2 text-fg': theme === 'dark' }"
                                            x-on:click="setTheme('dark'); themeOpen = false">
                                        <span class="grid size-6 shrink-0 place-items-center rounded-md bg-surface-2 text-fg-subtle">
                                            <i class="fad fa-fw fa-moon text-[11px]"></i>
                                        </span>
                                        <span class="min-w-0 flex-1 truncate">{{ __('chief::ui.shell.dark') }}</span>
                                        <i x-show="theme === 'dark'" class="fa fa-fw fa-check text-xs text-accent"></i>
                                    </button>
                                    <button type="button"
                                            class="flex w-full cursor-pointer items-center gap-2.5 rounded-md px-2.5 py-2 text-left text-sm font-medium text-fg-muted transition hover:bg-surface-2 hover:text-fg"
                                            x-bind:class="{ 'bg-surface-2 text-fg': theme === 'system' }"
                                            x-on:click="setTheme('system'); themeOpen = false">
                                        <span class="grid size-6 shrink-0 place-items-center rounded-md bg-surface-2 text-fg-subtle">
                                            <i class="fad fa-fw fa-display text-[11px]"></i>
                                        </span>
                                        <span class="min-w-0 flex-1 truncate">{{ __('chief::ui.shell.system') }}</span>
                                        <i x-show="theme === 'system'" class="fa fa-fw fa-check text-xs text-accent"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                @endguest

                @auth
                    @if(config('chief.teams') && auth()->user()->team)
                        <div class="relative" x-on:focusout="if ($event.relatedTarget && !$el.contains($event.relatedTarget)) teamOpen = false">
                            <button type="button"
                                    class="relative grid size-9 cursor-pointer place-items-center rounded-lg transition hover:bg-surface-2"
                                    x-ref="teamMenuButton"
                                    x-on:click="teamOpen = !teamOpen; accountOpen = false; themeOpen = false; menuOpen = false; closePalette(); if (teamOpen && $event.detail === 0) focusFirstMenuItem($refs.teamMenu)"
                                    x-on:keydown.arrow-down.prevent="if (!teamOpen) $el.click(); focusFirstMenuItem($refs.teamMenu)"
                                    x-bind:aria-expanded="teamOpen.toString()"
                                    aria-haspopup="menu">
                                <span class="sr-only">{{ __('chief::ui.shell.open_team_menu') }}</span>
                                <img class="size-8 rounded-md" src="{{ auth()->user()->team->avatar_url }}" alt="">
                                <span class="absolute bottom-0 right-0 grid size-4 place-items-center rounded-sm border-2 border-surface bg-accent text-[7px] font-bold leading-none text-accent-fg">T</span>
                            </button>

                            <div x-cloak
                                 x-ref="teamMenu"
                                 x-show="teamOpen"
                                 x-on:keydown.arrow-down.prevent="focusMenuItem($el, 1)"
                                 x-on:keydown.arrow-up.prevent="focusMenuItem($el, -1)"
                                 x-on:keydown.escape.stop="teamOpen = false; $refs.teamMenuButton.focus()"
                                 x-on:click.away="if ($el.offsetParent !== null) teamOpen = false"
                                 x-transition.origin.top.right
                                 class="absolute right-0 top-full z-50 mt-2 flex max-h-[calc(100dvh-5rem)] w-80 flex-col overflow-hidden rounded-lg border border-line bg-surface shadow-xl"
                                 role="menu">
                                @include('chief::partial.team.dropdown_items')
                            </div>
                        </div>
                    @endif

                    <div class="relative" x-on:focusout="if ($event.relatedTarget && !$el.contains($event.relatedTarget)) accountOpen = false">
                        <button type="button"
                                class="relative grid size-9 cursor-pointer place-items-center rounded-lg transition hover:bg-surface-2"
                                x-ref="accountMenuButton"
                                x-on:click="accountOpen = !accountOpen; teamOpen = false; themeOpen = false; menuOpen = false; closePalette(); if (accountOpen && $event.detail === 0) focusFirstMenuItem($refs.accountMenu)"
                                x-on:keydown.arrow-down.prevent="if (!accountOpen) $el.click(); focusFirstMenuItem($refs.accountMenu)"
                                x-bind:aria-expanded="accountOpen.toString()"
                                aria-haspopup="menu">
                            <span class="sr-only">{{ __('chief::ui.shell.open_account_menu') }}</span>
                            <img class="size-8 rounded-md" src="{{ auth()->user()->avatar_url }}" alt="">
                            @if(config('chief.teams') && auth()->user()->team)
                                <span class="absolute bottom-0 right-0 grid size-4 place-items-center rounded-sm border-2 border-surface bg-accent text-[7px] font-bold leading-none text-accent-fg">P</span>
                            @endif
                        </button>

                        <div x-cloak
                             x-ref="accountMenu"
                             x-show="accountOpen"
                             x-on:keydown.arrow-down.prevent="focusMenuItem($el, 1)"
                             x-on:keydown.arrow-up.prevent="focusMenuItem($el, -1)"
                             x-on:keydown.escape.stop="accountOpen = false; $refs.accountMenuButton.focus()"
                             x-on:click.away="if ($el.offsetParent !== null) accountOpen = false"
                             x-transition.origin.top.right
                             class="absolute right-0 top-full z-50 mt-2 w-80 overflow-hidden rounded-lg border border-line bg-surface shadow-xl"
                             role="menu">
                            @include('chief::partial.shell.account-menu')
                        </div>
                    </div>
                @endauth
            </div>
        </div>
    </header>

    <div x-cloak
         x-show="menuOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         id="chief-shell-mobile-menu"
         @class(['fixed inset-0 z-[55] bg-black/40 backdrop-blur-sm', 'md:hidden' => !$sidebarLayout, 'lg:hidden' => $sidebarLayout])
         x-bind:style="{ top: `${mobileMenuTop}px` }"
         x-on:click="menuOpen = false">
        <div x-show="menuOpen"
             x-ref="mobileMenu"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full"
             x-on:click.stop
             x-on:keydown.arrow-down.prevent="focusMenuItem($el, 1)"
             x-on:keydown.arrow-up.prevent="focusMenuItem($el, -1)"
             x-on:keydown.escape.stop="menuOpen = false; $refs.mobileMenuButton.focus()"
             x-on:focusout="if ($event.relatedTarget && !$el.contains($event.relatedTarget) && $event.relatedTarget !== $refs.mobileMenuButton) menuOpen = false"
             class="h-full w-72 overflow-y-auto overscroll-contain border-r border-line bg-surface shadow-xl">
            <div class="space-y-0.5 p-2">
                @include('chief::partial.shell.nav-tree', ['menuItems' => $mainMenuItems, 'touch' => true])
            </div>
            @if($pinnedMenuItems->isNotEmpty())
                <div class="space-y-0.5 border-t border-line p-2">
                    @include('chief::partial.shell.nav-tree', ['menuItems' => $pinnedMenuItems, 'touch' => true])
                </div>
            @endif
        </div>
    </div>

    @if($sidebarLayout)
        {{--
            The full height below the brand border, with what the header holds elsewhere: the app, team, search and account.
            Collapsed (`data-shell-sidebar` on the html element, remembered per browser) it is a rail of icons; the app's
            content moves right by its width either way.
        --}}
        <aside class="fixed top-1 bottom-0 left-0 z-30 hidden w-60 flex-col border-r border-line bg-surface lg:flex group-data-[shell-sidebar=collapsed]/chief:w-16">
            <div class="flex h-[53px] shrink-0 items-center border-b border-line px-3 group-data-[shell-sidebar=collapsed]/chief:justify-center group-data-[shell-sidebar=collapsed]/chief:px-0">
                @include('chief::partial.shell.logo', ['rail' => true])
            </div>

            <div class="shrink-0 space-y-2 px-3 pt-3">
                @auth
                    @if(config('chief.teams') && auth()->user()->team)
                        <div class="relative" x-on:focusout="if ($event.relatedTarget && !$el.contains($event.relatedTarget)) teamOpen = false">
                            <button type="button"
                                    class="flex h-10 w-full cursor-pointer items-center gap-2.5 rounded-lg px-1.5 text-left transition hover:bg-surface-2 group-data-[shell-sidebar=collapsed]/chief:justify-center group-data-[shell-sidebar=collapsed]/chief:px-0"
                                    x-ref="sidebarTeamButton"
                                    x-on:click="teamOpen = !teamOpen; accountOpen = false; themeOpen = false; closePalette(); hideRailTip(); if (teamOpen && $event.detail === 0) focusFirstMenuItem($refs.sidebarTeamMenu)"
                                    x-on:keydown.arrow-down.prevent="if (!teamOpen) $el.click(); focusFirstMenuItem($refs.sidebarTeamMenu)"
                                    x-on:mouseenter="showRailTip($el)"
                                    x-on:mouseleave="hideRailTip()"
                                    data-shell-label="{{ auth()->user()->team->name }}"
                                    x-bind:aria-expanded="teamOpen.toString()"
                                    aria-haspopup="menu">
                                <span class="sr-only">{{ __('chief::ui.shell.open_team_menu') }}</span>
                                <img class="size-7 shrink-0 rounded-md" src="{{ auth()->user()->team->avatar_url }}" alt="">
                                <span class="min-w-0 flex-1 truncate text-sm font-medium text-fg group-data-[shell-sidebar=collapsed]/chief:hidden">{{ auth()->user()->team->name }}</span>
                                {{-- Font Awesome's display rule outranks hiding the icon itself --}}
                                <span class="group-data-[shell-sidebar=collapsed]/chief:hidden"><i class="fa fa-fw fa-chevron-down text-[10px] text-fg-faint"></i></span>
                            </button>

                            <div x-cloak
                                 x-ref="sidebarTeamMenu"
                                 x-show="teamOpen"
                                 x-on:keydown.arrow-down.prevent="focusMenuItem($el, 1)"
                                 x-on:keydown.arrow-up.prevent="focusMenuItem($el, -1)"
                                 x-on:keydown.escape.stop="teamOpen = false; $refs.sidebarTeamButton.focus()"
                                 x-on:click.away="if ($el.offsetParent !== null) teamOpen = false"
                                 x-transition.origin.top.left
                                 class="absolute top-full left-0 z-50 mt-1 flex max-h-[calc(100dvh-8rem)] w-72 flex-col overflow-hidden rounded-lg border border-line bg-surface shadow-xl group-data-[shell-sidebar=collapsed]/chief:top-0 group-data-[shell-sidebar=collapsed]/chief:left-full group-data-[shell-sidebar=collapsed]/chief:mt-0 group-data-[shell-sidebar=collapsed]/chief:ml-3"
                                 role="menu">
                                @include('chief::partial.team.dropdown_items')
                            </div>
                        </div>
                    @endif
                @endauth

                @if(config('chief.shell.command_palette'))
                    <button type="button"
                            class="flex h-8 w-full cursor-pointer items-center gap-2 rounded-md border border-line bg-surface-2 px-2 text-xs text-fg-subtle transition hover:border-line-strong hover:bg-surface-3 hover:text-fg-muted group-data-[shell-sidebar=collapsed]/chief:justify-center group-data-[shell-sidebar=collapsed]/chief:px-0"
                            x-on:click="openPalette(); hideRailTip()"
                            x-on:mouseenter="showRailTip($el)"
                            x-on:mouseleave="hideRailTip()"
                            data-shell-label="{{ __('chief::ui.shell.search') }} (⌘K)">
                        <i class="fa fa-fw fa-search"></i>
                        <span class="flex-1 truncate text-left group-data-[shell-sidebar=collapsed]/chief:hidden">{{ __('chief::ui.shell.search_or_jump') }}</span>
                        <span class="rounded border border-line bg-surface px-1.5 py-0.5 font-mono text-[10px] font-medium text-fg-subtle group-data-[shell-sidebar=collapsed]/chief:hidden">⌘K</span>
                    </button>
                @endif
            </div>

            <nav x-ref="shellSidebar" class="min-h-0 flex-1 space-y-0.5 overflow-y-auto overscroll-contain px-3 py-3 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" aria-label="{{ __('chief::ui.shell.main_menu') }}">
                @include('chief::partial.shell.nav-tree', ['menuItems' => $mainMenuItems])
            </nav>

            <div class="shrink-0 space-y-0.5 border-t border-line px-3 py-3">
                @if($pinnedMenuItems->isNotEmpty())
                    @include('chief::partial.shell.nav-tree', ['menuItems' => $pinnedMenuItems])
                @endif

                @stack('chief.shell.actions')

                @guest
                    @if(config('chief.shell.theme_selector', true) && $themeUpdateUrl)
                        <div class="flex gap-1 rounded-md bg-surface-2 p-1 group-data-[shell-sidebar=collapsed]/chief:flex-col">
                            @foreach(['light' => 'fa-sun-bright', 'dark' => 'fa-moon', 'system' => 'fa-display'] as $themeOption => $themeIcon)
                                <button type="button"
                                        class="flex flex-1 cursor-pointer items-center justify-center gap-1 rounded px-2 py-1 text-xs font-medium transition"
                                        x-bind:class="themeButtonClasses(@js($themeOption))"
                                        x-on:click="setTheme(@js($themeOption))"
                                        aria-label="{{ __('chief::ui.shell.' . $themeOption) }}">
                                    <i class="fad fa-fw {{ $themeIcon }} text-[11px]"></i>
                                    <span class="group-data-[shell-sidebar=collapsed]/chief:hidden">{{ __('chief::ui.shell.' . $themeOption) }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                @endguest

                <div class="flex items-center gap-1 group-data-[shell-sidebar=collapsed]/chief:flex-col">
                    @auth
                        <div class="relative min-w-0 flex-1 group-data-[shell-sidebar=collapsed]/chief:flex-none" x-on:focusout="if ($event.relatedTarget && !$el.contains($event.relatedTarget)) accountOpen = false">
                            <button type="button"
                                    class="flex h-10 w-full cursor-pointer items-center gap-2.5 rounded-lg px-1.5 text-left transition hover:bg-surface-2 group-data-[shell-sidebar=collapsed]/chief:justify-center group-data-[shell-sidebar=collapsed]/chief:px-0"
                                    x-ref="sidebarAccountButton"
                                    x-on:click="accountOpen = !accountOpen; teamOpen = false; themeOpen = false; closePalette(); hideRailTip(); if (accountOpen && $event.detail === 0) focusFirstMenuItem($refs.sidebarAccountMenu)"
                                    x-on:keydown.arrow-up.prevent="if (!accountOpen) $el.click(); focusFirstMenuItem($refs.sidebarAccountMenu)"
                                    x-on:mouseenter="showRailTip($el)"
                                    x-on:mouseleave="hideRailTip()"
                                    data-shell-label="{{ auth()->user() }}"
                                    x-bind:aria-expanded="accountOpen.toString()"
                                    aria-haspopup="menu">
                                <span class="sr-only">{{ __('chief::ui.shell.open_account_menu') }}</span>
                                <img class="size-7 shrink-0 rounded-md" src="{{ auth()->user()->avatar_url }}" alt="">
                                <span class="min-w-0 flex-1 group-data-[shell-sidebar=collapsed]/chief:hidden">
                                    <span class="block truncate text-sm font-medium text-fg">{{ auth()->user() }}</span>
                                    <span class="block truncate text-xs text-fg-subtle">{{ auth()->user()->email }}</span>
                                </span>
                            </button>

                            <div x-cloak
                                 x-ref="sidebarAccountMenu"
                                 x-show="accountOpen"
                                 x-on:keydown.arrow-down.prevent="focusMenuItem($el, 1)"
                                 x-on:keydown.arrow-up.prevent="focusMenuItem($el, -1)"
                                 x-on:keydown.escape.stop="accountOpen = false; $refs.sidebarAccountButton.focus()"
                                 x-on:click.away="if ($el.offsetParent !== null) accountOpen = false"
                                 x-transition.origin.bottom.left
                                 class="absolute bottom-full left-0 z-50 mb-2 max-h-[calc(100dvh-6rem)] w-72 overflow-y-auto rounded-lg border border-line bg-surface shadow-xl group-data-[shell-sidebar=collapsed]/chief:bottom-0 group-data-[shell-sidebar=collapsed]/chief:left-full group-data-[shell-sidebar=collapsed]/chief:mb-0 group-data-[shell-sidebar=collapsed]/chief:ml-3"
                                 role="menu">
                                @include('chief::partial.shell.account-menu')
                            </div>
                        </div>
                    @else
                        <div class="flex-1"></div>
                    @endauth

                    <button type="button"
                            class="grid size-8 shrink-0 cursor-pointer place-items-center rounded-md text-fg-faint transition hover:bg-surface-2 hover:text-fg-muted"
                            x-on:click="toggleSidebar()"
                            x-on:mouseenter="showRailTip($el)"
                            x-on:mouseleave="hideRailTip()"
                            data-shell-label="{{ __('chief::ui.shell.expand_sidebar') }}"
                            x-bind:aria-label="sidebarCollapsed ? @js(__('chief::ui.shell.expand_sidebar')) : @js(__('chief::ui.shell.collapse_sidebar'))"
                            x-bind:aria-expanded="(!sidebarCollapsed).toString()">
                        <i class="fa fa-fw fa-sidebar text-sm"></i>
                    </button>
                </div>
            </div>

            {{-- The label of what the pointer is on in the rail, outside the scrolling menu so it is not cut off --}}
            <div x-cloak
                 x-show="railTip.visible"
                 x-bind:style="{ top: `${railTip.top}px`, left: `${railTip.left}px` }"
                 x-text="railTip.text"
                 class="pointer-events-none fixed z-50 -translate-y-1/2 rounded-md border border-line bg-surface-raised px-2 py-1 text-xs font-medium whitespace-nowrap text-fg shadow-lg"></div>
        </aside>
    @endif

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
                    <button type="button"
                            x-cloak
                            x-show="hasNestedPaletteContext()"
                            x-on:click="leavePaletteContext()"
                            class="grid size-7 shrink-0 cursor-pointer place-items-center rounded-md text-fg-subtle transition hover:bg-surface-2 hover:text-fg"
                            aria-label="{{ __('chief::ui.shell.back_to_results') }}">
                        <i class="fa fa-fw fa-arrow-left text-xs"></i>
                    </button>
                    <i x-show="!hasNestedPaletteContext()" class="fa fa-fw text-fg-faint" x-bind:class="remoteLoading ? 'fa-spinner-third fa-spin' : 'fa-search'"></i>
                    <input x-ref="paletteSearch"
                           x-model="paletteQuery"
                           x-on:input="paletteQueryChanged()"
                           x-on:keydown.arrow-down.prevent="movePalette(1)"
                           x-on:keydown.arrow-up.prevent="movePalette(-1)"
                           x-on:keydown.arrow-right="handlePaletteRight($event)"
                           x-on:keydown.arrow-left="handlePaletteLeft($event)"
                           x-on:keydown.backspace="handlePaletteBackspace($event)"
                           x-on:keydown.enter.prevent="activatePalette()"
                           type="search"
                           class="min-w-0 flex-1 border-0 bg-transparent p-0 text-sm text-fg placeholder:text-fg-faint focus:ring-0">
                    <span class="rounded border border-line bg-surface-2 px-1.5 py-0.5 font-mono text-[10px] font-medium text-fg-subtle">esc</span>
                </div>

                <div x-show="!hasNestedPaletteContext()" class="flex gap-2 border-b border-line bg-surface-2 px-3 py-2 text-xs text-fg-subtle">
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

                <div x-cloak x-show="hasNestedPaletteContext()" class="border-b border-line bg-surface-2 px-4 py-2 text-xs font-medium text-fg-subtle">
                    <span x-text="paletteContextTitle()"></span>
                </div>

                <div x-ref="paletteItems" class="flex max-h-96 flex-col overflow-y-auto p-1.5">
                    @if($commandContextLabel !== '' && $contextCommands->isNotEmpty())
                        <div x-show="!hasNestedPaletteContext() && !paletteQuery.trim()"
                             class="px-3 pb-1 pt-2 text-[11px] font-semibold uppercase tracking-wide text-fg-faint"
                             style="order: 100">
                            {{ __('chief::ui.shell.for_context', ['label' => $commandContextLabel]) }}
                        </div>

                        @foreach($contextCommands as $command)
                            <a href="{{ $command['href'] }}"
                               class="group flex items-center gap-3 rounded-md px-3 py-2 text-fg-muted transition data-[active=true]:bg-surface-2 data-[active=true]:text-fg"
                               data-shell-command
                               data-shell-category="{{ $command['category'] }}"
                               data-shell-title="{{ strtolower($command['label']) }}"
                               data-shell-body="{{ strtolower(trim($command['description'] . ' ' . $command['keywords'])) }}"
                               x-show="!hasNestedPaletteContext() && matchesCommand(paletteQuery, $el.dataset.shellTitle, $el.dataset.shellCategory, $el.dataset.shellBody)"
                               x-bind:style="{ order: paletteItemOrder(paletteQuery, $el.dataset.shellTitle, $el.dataset.shellCategory, $el.dataset.shellBody, 110) }"
                               x-on:mousemove="activeIndex = visiblePaletteItems().indexOf($el); syncPaletteActive()"
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
                                <i class="fa fa-fw fa-chevron-right text-xs text-fg-faint"></i>
                            </a>
                        @endforeach

                        <div x-show="!hasNestedPaletteContext() && !paletteQuery.trim()"
                             class="border-t border-line px-3 pb-1 pt-3 text-[11px] font-semibold uppercase tracking-wide text-fg-faint"
                             style="order: 200">
                            {{ __('chief::ui.shell.all_commands') }}
                        </div>
                    @endif

                    @foreach($commandMenuItems as $item)
                        @php($label = $item['text'] ?? $item['label'] ?? '')
                        <a href="{{ $item['href'] ?? '#' }}"
                           class="group flex items-center gap-3 rounded-md px-3 py-2 text-fg-muted transition data-[active=true]:bg-surface-2 data-[active=true]:text-fg"
                           data-shell-command
                           data-shell-category="{{ $tool['name'] }}"
                           data-shell-title="{{ strtolower($label) }}"
                           data-shell-body="{{ strtolower(trim($tool['name'] . ' ' . ($item['parent'] ?? ''))) }}"
                           x-show="!hasNestedPaletteContext() && matchesCommand(paletteQuery, $el.dataset.shellTitle, $el.dataset.shellCategory, $el.dataset.shellBody)"
                           x-bind:style="{ order: paletteItemOrder(paletteQuery, $el.dataset.shellTitle, $el.dataset.shellCategory, $el.dataset.shellBody, 210) }"
                           x-on:mousemove="activeIndex = visiblePaletteItems().indexOf($el); syncPaletteActive()"
                           @if(!empty($item['wire'])) wire:navigate @endif
                           x-on:click="closePalette()">
                            @if(!empty($item['icon']))
                                <span class="grid size-7 place-items-center rounded-md bg-surface-2 text-fg-subtle">
                                    <i class="fa-fw {{ $item['icon'] }} text-sm"></i>
                                </span>
                            @endif
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium">{{ $label }}</span>
                                <span class="block truncate text-xs text-fg-subtle">{{ $tool['name'] }} &gt; @if(!empty($item['parent'])){{ $item['parent'] }} &gt; @endif{{ $label }}</span>
                            </span>
                            <i class="fa fa-fw fa-chevron-right text-xs text-fg-faint"></i>
                        </a>
                    @endforeach

                    @foreach($commands as $command)
                        <a href="{{ $command['href'] }}"
                           class="group flex items-center gap-3 rounded-md px-3 py-2 text-fg-muted transition data-[active=true]:bg-surface-2 data-[active=true]:text-fg"
                           data-shell-command
                           data-shell-category="{{ $command['category'] }}"
                           data-shell-title="{{ strtolower($command['label']) }}"
                           data-shell-body="{{ strtolower(trim($command['description'] . ' ' . $command['keywords'])) }}"
                           x-show="!hasNestedPaletteContext() && matchesCommand(paletteQuery, $el.dataset.shellTitle, $el.dataset.shellCategory, $el.dataset.shellBody)"
                           x-bind:style="{ order: paletteItemOrder(paletteQuery, $el.dataset.shellTitle, $el.dataset.shellCategory, $el.dataset.shellBody, 210) }"
                           x-on:mousemove="activeIndex = visiblePaletteItems().indexOf($el); syncPaletteActive()"
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
                            <i class="fa fa-fw fa-chevron-right text-xs text-fg-faint"></i>
                        </a>
                    @endforeach

                    @if(config('chief.shell.theme_selector', true))
                        @foreach($themeCommands as $themeCommand)
                            <button type="button"
                                    class="group flex cursor-pointer items-center gap-3 rounded-md px-3 py-2 text-left text-fg-muted transition data-[active=true]:bg-surface-2 data-[active=true]:text-fg"
                                    data-shell-command
                                    data-shell-category="Theme"
                                    data-shell-title="{{ strtolower($themeCommand['label']) }}"
                                    data-shell-body="{{ strtolower($themeCommand['description']) }}"
                                    x-show="!hasNestedPaletteContext() && theme !== @js($themeCommand['theme']) && matchesCommand(paletteQuery, $el.dataset.shellTitle, $el.dataset.shellCategory, $el.dataset.shellBody)"
                                    x-bind:style="{ order: paletteItemOrder(paletteQuery, $el.dataset.shellTitle, $el.dataset.shellCategory, $el.dataset.shellBody, 210) }"
                                    x-on:mousemove="activeIndex = visiblePaletteItems().indexOf($el); syncPaletteActive()"
                                    x-on:click="setTheme(@js($themeCommand['theme'])); closePalette()">
                                <span class="grid size-7 place-items-center rounded-md bg-surface-2 text-fg-subtle">
                                    <i class="fa-fw {{ $themeCommand['icon'] }} text-sm"></i>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium">{{ $themeCommand['label'] }}</span>
                                    <span class="block truncate text-xs text-fg-subtle">Theme &gt; {{ $themeCommand['description'] }}</span>
                                </span>
                                <i class="fa fa-fw fa-check text-xs text-fg-faint"></i>
                            </button>
                        @endforeach
                    @endif

                    @if($layoutCommand)
                        <button type="button"
                                class="group flex cursor-pointer items-center gap-3 rounded-md px-3 py-2 text-left text-fg-muted transition data-[active=true]:bg-surface-2 data-[active=true]:text-fg"
                                data-shell-command
                                data-shell-category="Layout"
                                data-shell-title="{{ strtolower($layoutCommand['label']) }}"
                                data-shell-body="{{ strtolower($layoutCommand['description'] . ' layout menu navigation sidebar top bar') }}"
                                x-show="!hasNestedPaletteContext() && matchesCommand(paletteQuery, $el.dataset.shellTitle, $el.dataset.shellCategory, $el.dataset.shellBody)"
                                x-bind:style="{ order: paletteItemOrder(paletteQuery, $el.dataset.shellTitle, $el.dataset.shellCategory, $el.dataset.shellBody, 210) + 500 }"
                                x-on:mousemove="activeIndex = visiblePaletteItems().indexOf($el); syncPaletteActive()"
                                x-on:click="setLayout(@js($layoutCommand['layout'])); closePalette()">
                            <span class="grid size-7 place-items-center rounded-md bg-surface-2 text-fg-subtle">
                                <i class="fa-fw {{ $layoutCommand['icon'] }} text-sm"></i>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium">{{ $layoutCommand['label'] }}</span>
                                <span class="block truncate text-xs text-fg-subtle">Layout &gt; {{ $layoutCommand['description'] }}</span>
                            </span>
                            <i class="fa fa-fw fa-check text-xs text-fg-faint"></i>
                        </button>
                    @endif

                    @foreach($apps as $app)
                        <a href="{{ $app['href'] }}"
                           class="group flex items-center gap-3 rounded-md px-3 py-2 text-fg-muted transition data-[active=true]:bg-surface-2 data-[active=true]:text-fg"
                           data-shell-command
                           data-shell-category="Chief Tools"
                           data-shell-title="{{ strtolower($app['name'] . ' ' . $app['short']) }}"
                           data-shell-body="{{ strtolower($app['description']) }}"
                           x-show="!hasNestedPaletteContext() && matchesCommand(paletteQuery, $el.dataset.shellTitle, $el.dataset.shellCategory, $el.dataset.shellBody)"
                           x-bind:style="{ order: paletteItemOrder(paletteQuery, $el.dataset.shellTitle, $el.dataset.shellCategory, $el.dataset.shellBody, 210) }"
                           x-on:mousemove="activeIndex = visiblePaletteItems().indexOf($el); syncPaletteActive()"
                           @if($app['target']) target="{{ $app['target'] }}" @endif
                           @if($app['target'] === '_blank') rel="noopener" @endif
                           x-on:click="closePalette()">
                            <span class="grid size-8 place-items-center rounded-md text-white" style="background-color: {{ $app['color'] }};">
                                @if($app['logoUrl'])
                                    <img src="{{ $app['logoUrl'] }}" alt="" class="max-h-6 max-w-6">
                                @elseif($app['brandIcon'])
                                    <span class="fa-stack text-[1rem] leading-none">
                                        <i class="fad {{ $app['icon'] }} fa-stack-2x"></i>
                                        <i class="fab {{ $app['brandIcon'] }} fa-stack-1x" style="font-size: 8px;"></i>
                                    </span>
                                @else
                                    <i class="fad fa-fw {{ $app['icon'] }} text-xl"></i>
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
                            <i class="fa fa-fw fa-arrow-up-right text-xs text-fg-faint"></i>
                        </a>
                    @endforeach

                    <template x-for="result in remoteResults" x-bind:key="result.id">
                        <div class="group flex items-center rounded-md text-fg-muted transition data-[active=true]:bg-surface-2 data-[active=true]:text-fg"
                             data-shell-command
                             x-bind:data-shell-command-id="String(result.id || '')"
                             x-bind:data-shell-category="result.category || ''"
                             x-bind:data-shell-title="result.title || ''"
                             x-bind:data-shell-body="[result.description || '', ...(result.keywords || [])].join(' ')"
                             x-bind:style="{ order: hasNestedPaletteContext() ? commandOrder(paletteQuery, result.title || '', result.category || '', [result.description || '', ...(result.keywords || [])].join(' ')) : (result.order || 9500) }"
                             x-show="!hasNestedPaletteContext() || matchesCommand(paletteQuery, result.title || '', result.category || '', [result.description || '', ...(result.keywords || [])].join(' '))"
                             x-on:mousemove="activeIndex = visiblePaletteItems().indexOf($el); syncPaletteActive()">
                            <a x-bind:href="result.url || '#'"
                               data-shell-command-primary
                               class="flex min-w-0 flex-1 items-center gap-3 px-3 py-2"
                               x-bind:target="result.target || null"
                               x-bind:rel="result.target === '_blank' ? 'noopener' : null"
                               x-on:click="closePalette()">
                                <span class="relative grid size-7 shrink-0 place-items-center overflow-hidden rounded-md bg-surface-2 text-fg-subtle">
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
                                <i x-show="!hasPaletteChildren(result)" class="fa fa-fw fa-chevron-right text-xs text-fg-faint"></i>
                            </a>
                            <button type="button"
                                    x-show="hasPaletteChildren(result)"
                                    x-on:click.stop="enterPaletteChildren(result)"
                                    data-shell-command-browse
                                    class="mr-1 flex h-8 shrink-0 cursor-pointer items-center gap-1.5 rounded-md px-2 text-xs font-medium text-fg-subtle transition hover:bg-surface-3 hover:text-fg"
                                    x-bind:aria-label="@js(__('chief::ui.shell.show_destinations_for')) + ' ' + (result.title || 'result')">
                                <span>{{ __('chief::ui.shell.browse') }}</span>
                                <i class="fa fa-fw fa-chevron-right text-xs"></i>
                            </button>
                        </div>
                    </template>

                    <div x-cloak x-show="remoteError" class="rounded-md px-3 py-2 text-xs text-red">
                        {{ __('chief::ui.shell.search_results_unavailable') }}
                    </div>

                    <div x-cloak
                         x-show="hasPaletteSearchTerm() && !remoteLoading && !remoteError && !hasPaletteItems()"
                         class="rounded-md px-3 py-6 text-center text-sm text-fg-subtle">
                        {{ __('chief::ui.shell.no_results') }}
                    </div>
                </div>

                <div class="flex items-center gap-4 border-t border-line bg-surface-2 px-4 py-2 text-xs text-fg-subtle">
                    <span class="flex items-center gap-1">
                        <span class="rounded border border-line bg-surface px-1.5 py-0.5 font-mono text-[10px]">↑</span>
                        <span class="rounded border border-line bg-surface px-1.5 py-0.5 font-mono text-[10px]">↓</span> {{ __('chief::ui.shell.move') }}
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="rounded border border-line bg-surface px-1.5 py-0.5 font-mono text-[10px]">Enter</span> {{ __('chief::ui.shell.open') }}
                    </span>
                    <span x-cloak x-show="activePaletteHasChildren()" class="flex items-center gap-1">
                        <span class="rounded border border-line bg-surface px-1.5 py-0.5 font-mono text-[10px]">→</span> {{ __('chief::ui.shell.browse') }}
                    </span>
                    <span x-cloak x-show="hasNestedPaletteContext()" class="flex items-center gap-1">
                        <span class="rounded border border-line bg-surface px-1.5 py-0.5 font-mono text-[10px]">←</span> {{ __('chief::ui.shell.back') }}
                    </span>
                    @if($apps->isNotEmpty())
                        <a href="{{ $allAppsUrl }}" target="_blank" rel="noopener" class="ml-auto inline-flex items-center gap-1 font-medium text-fg-muted hover:text-fg">
                            <span>{{ __('chief::ui.shell.all_apps') }}</span>
                            <i class="fa fa-fw fa-arrow-up-right-from-square text-[10px] text-fg-faint"></i>
                        </a>
                    @else
                        <span class="ml-auto">{{ __('chief::ui.shell.scope_results') }}</span>
                    @endif
                </div>
            </div>
        </div>
    @endif
</nav>

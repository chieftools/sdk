@php
    $minimalMenu = isset($minimalMenu) && $minimalMenu;
    $fullwidthMenu = $minimalMenu || (isset($fullwidthMenu) && $fullwidthMenu);
    $menuItems = collect($menuItems ?? [])->values();
    $themeCookieName = config('chief.shell.theme_cookie', 'chief_shell_theme');
    $themePreference = request()->cookie($themeCookieName, config('chief.shell.theme', 'light'));
    $themePreference = in_array($themePreference, ['light', 'dark', 'system'], true) ? $themePreference : 'light';
    $theme = $themePreference === 'dark' ? 'dark' : 'light';
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
        'group/shell relative z-40 bg-white text-gray-950 dark:bg-gray-950 dark:text-gray-100',
        'dark' => $theme === 'dark',
     ])
     style="--chief-shell-accent: {{ $tool['color'] }};"
     x-data="chiefShell"
     x-on:keydown.window.escape="menuOpen = false; closePanels()"
     x-on:keydown.window="if (($event.metaKey || $event.ctrlKey) && $event.key.toLowerCase() === 'k') { $event.preventDefault(); openPalette(); }"
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

    <header class="border-b border-gray-200 bg-white dark:border-white/10 dark:bg-gray-950">
        <div @class([
            'relative flex h-[52px] items-stretch gap-2 px-2 sm:px-6 lg:px-8',
            'container mx-auto max-w-7xl' => !$fullwidthMenu,
            'w-full' => $fullwidthMenu,
        ])>
            <button type="button"
                    class="grid size-9 cursor-pointer place-items-center self-center rounded-md text-gray-500 transition hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-900 dark:hover:text-gray-100 md:hidden"
                    x-on:click="menuOpen = !menuOpen; accountOpen = false; teamOpen = false; closePalette()"
                    aria-controls="chief-shell-mobile-menu"
                    x-bind:aria-expanded="menuOpen.toString()">
                <span class="sr-only">Toggle main menu</span>
                <i class="fa fa-fw fa-bars" x-show="!menuOpen"></i>
                <i class="fa fa-fw fa-xmark" x-show="menuOpen"></i>
            </button>

            <div class="relative flex items-center">
                @if(config('chief.shell.app_switcher') && config('chief.shell.command_palette'))
                    <button type="button"
                            class="flex h-10 cursor-pointer items-center gap-2 rounded-lg px-1 pr-2 text-gray-950 transition hover:bg-gray-50 dark:text-gray-100 dark:hover:bg-gray-900"
                            x-on:click="openPalette('Chief Tools > ')"
                            x-bind:aria-expanded="paletteOpen.toString()"
                            aria-haspopup="dialog">
                @else
                    <a href="{{ $tool['href'] }}" class="text flex h-10 items-center gap-2 rounded-lg px-1 pr-2 text-gray-950 transition hover:bg-gray-50 dark:text-gray-100 dark:hover:bg-gray-900">
                @endif
                        <span class="grid size-9 shrink-0 place-items-center rounded-md text-[var(--chief-shell-accent)]">
                            @if(!empty($toolLogoUrl))
                                <img src="{{ $toolLogoUrl }}" alt="" class="max-h-8 max-w-8">
                            @else
                                <i class="fad fa-fw {{ $tool['icon'] }} text-2xl"></i>
                            @endif
                        </span>
                        <span class="hidden max-w-48 truncate text-sm font-semibold md:block">{{ $tool['name'] }}</span>
                        @if(config('chief.shell.app_switcher') && config('chief.shell.command_palette'))
                            <i class="fa fa-fw fa-chevron-down text-[10px] text-gray-400 dark:text-gray-500"></i>
                        @endif
                @if(config('chief.shell.app_switcher') && config('chief.shell.command_palette'))
                    </button>
                @else
                    </a>
                @endif
            </div>

            @unless($menuItems->isEmpty())
                <div class="hidden w-px self-center bg-gray-200 dark:bg-white/10 md:block"></div>

                <div @class([
                    'hidden min-w-0 flex-1 items-stretch overflow-x-auto md:flex',
                    'justify-center md:absolute md:inset-y-0 md:left-1/2 md:w-max md:max-w-[calc(100%-2rem)] md:-translate-x-1/2 md:flex-none md:overflow-x-visible' => $fullwidthMenu,
                ])>
                    @foreach($menuItems as $item)
                        @include('chief::partial.shell.nav-item', ['item' => $item])
                    @endforeach
                </div>
            @else
                <div class="flex-1"></div>
            @endunless

            <div class="flex flex-1 items-center gap-1 md:hidden">
                <i class="fa-fw {{ $mobileIcon }} text-sm text-[var(--chief-shell-accent)]"></i>
                <span class="min-w-0 truncate text-sm font-medium text-gray-900 dark:text-gray-100">{{ $mobileTitle }}</span>
            </div>

            <div @class([
                'flex items-center gap-1 self-center',
                'md:ml-auto' => $fullwidthMenu,
            ])>
                @if(config('chief.shell.command_palette'))
                    <button type="button"
                            class="hidden h-8 min-w-52 cursor-pointer items-center gap-2 rounded-md border border-gray-200 bg-gray-50 px-2 text-xs text-gray-500 transition hover:border-gray-300 hover:bg-white hover:text-gray-700 dark:border-white/10 dark:bg-gray-900 dark:text-gray-400 dark:hover:border-white/15 dark:hover:bg-gray-800 dark:hover:text-gray-200 lg:flex"
                            x-on:click="openPalette()">
                        <i class="fa fa-fw fa-search"></i>
                        <span class="flex-1 text-left">Search or jump to...</span>
                        <span class="rounded border border-gray-200 bg-white px-1.5 py-0.5 font-mono text-[10px] font-medium text-gray-500 dark:border-white/10 dark:bg-gray-950 dark:text-gray-400">Cmd K</span>
                    </button>

                    <button type="button"
                            class="grid size-8 cursor-pointer place-items-center rounded-md bg-gray-50 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200 lg:hidden"
                            x-on:click="openPalette()">
                        <span class="sr-only">Search</span>
                        <i class="fad fa-fw fa-search text-sm"></i>
                    </button>
                @endif

                @auth
                    @if(config('chief.teams') && auth()->user()->team)
                        <div class="relative">
                            <button type="button"
                                    class="relative grid size-9 cursor-pointer place-items-center rounded-lg transition hover:bg-gray-50 dark:hover:bg-gray-900"
                                    x-on:click="teamOpen = !teamOpen; accountOpen = false; menuOpen = false; closePalette()"
                                    x-bind:aria-expanded="teamOpen.toString()"
                                    aria-haspopup="menu">
                                <span class="sr-only">Open team menu</span>
                                <img class="size-8 rounded-md" src="{{ auth()->user()->team->avatar_url }}" alt="">
                                <span class="absolute bottom-0 right-0 grid size-4 place-items-center rounded-sm border-2 border-white bg-[var(--chief-shell-accent)] text-[7px] font-bold leading-none text-white dark:border-gray-950">T</span>
                            </button>

                            <div x-cloak
                                 x-show="teamOpen"
                                 x-on:click.away="teamOpen = false"
                                 x-transition.origin.top.right
                                 class="absolute right-0 top-full z-50 mt-2 w-80 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-xl dark:border-white/10 dark:bg-gray-900"
                                 role="menu">
                                @include('chief::partial.team.dropdown_items')
                            </div>
                        </div>
                    @endif

                    <div class="relative">
                        <button type="button"
                                class="relative grid size-9 cursor-pointer place-items-center rounded-lg transition hover:bg-gray-50 dark:hover:bg-gray-900"
                                x-on:click="accountOpen = !accountOpen; teamOpen = false; menuOpen = false; closePalette()"
                                x-bind:aria-expanded="accountOpen.toString()"
                                aria-haspopup="menu">
                            <span class="sr-only">Open account menu</span>
                            <img class="size-8 rounded-md" src="{{ auth()->user()->avatar_url }}" alt="">
                            @if(config('chief.teams') && auth()->user()->team)
                                <span class="absolute bottom-0 right-0 grid size-4 place-items-center rounded-sm border-2 border-white bg-[var(--chief-shell-accent)] text-[7px] font-bold leading-none text-white dark:border-gray-950">P</span>
                            @endif
                        </button>

                        <div x-cloak
                             x-show="accountOpen"
                             x-on:click.away="accountOpen = false"
                             x-transition.origin.top.right
                             class="absolute right-0 top-full z-50 mt-2 w-80 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-xl dark:border-white/10 dark:bg-gray-900"
                             role="menu">
                            <div class="flex items-center gap-3 px-4 py-3">
                                <img class="size-9 rounded-lg" src="{{ auth()->user()->avatar_url }}" alt="">
                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-sm font-medium text-gray-950 dark:text-gray-100">{{ auth()->user() }}</div>
                                    <div class="truncate text-xs text-gray-500 dark:text-gray-400">{{ auth()->user()->email }}</div>
                                </div>
                            </div>

                            @if(config('chief.shell.theme_selector', true))
                                <div class="border-t border-gray-200 px-3 py-2.5 dark:border-white/10" role="none">
                                    <div class="flex gap-1 rounded-md bg-gray-50 p-1 dark:bg-gray-800">
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
         class="fixed inset-0 top-[55px] z-50 bg-gray-950/40 backdrop-blur-sm md:hidden"
         x-on:click="menuOpen = false">
        <div class="h-full w-72 border-r border-gray-200 bg-white shadow-xl dark:border-white/10 dark:bg-gray-900" x-on:click.stop x-transition>
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
                           'bg-gray-50 text-gray-950 dark:bg-gray-800 dark:text-gray-100' => $active,
                           'text-gray-600 hover:bg-gray-50 hover:text-gray-950 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100' => !$active,
                       ])
                       @if(!empty($item['wire'])) wire:navigate @endif
                       x-on:click="menuOpen = false">
                        @if($icon)
                            <i @class([
                                'fa-fw text-base',
                                $icon,
                                'text-[var(--chief-shell-accent)]' => $active,
                                'text-gray-400 group-hover:text-gray-500 dark:text-gray-400 dark:group-hover:text-gray-200' => !$active,
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
             class="fixed inset-0 z-[60] bg-gray-950/40 p-4 pt-[12vh] backdrop-blur-sm"
             x-on:click="closePalette()">
            <div class="mx-auto max-w-xl overflow-hidden rounded-xl border border-gray-200 bg-white shadow-2xl dark:border-white/10 dark:bg-gray-900" x-on:click.stop x-transition>
                <div class="flex items-center gap-3 border-b border-gray-200 px-4 py-3 dark:border-white/10">
                    <i class="fa fa-fw text-gray-400 dark:text-gray-400" x-bind:class="remoteLoading ? 'fa-spinner-third fa-spin' : 'fa-search'"></i>
                    <input x-ref="paletteSearch"
                           x-model="paletteQuery"
                           x-on:input="paletteQueryChanged()"
                           x-on:keydown.arrow-down.prevent="movePalette(1)"
                           x-on:keydown.arrow-up.prevent="movePalette(-1)"
                           x-on:keydown.enter.prevent="activatePalette()"
                           type="search"
                           class="min-w-0 flex-1 border-0 bg-transparent p-0 text-sm text-gray-950 placeholder:text-gray-400 focus:ring-0 dark:text-gray-100 dark:placeholder:text-gray-500">
                    <span class="rounded border border-gray-200 bg-gray-50 px-1.5 py-0.5 font-mono text-[10px] font-medium text-gray-500 dark:border-white/10 dark:bg-gray-950 dark:text-gray-400">esc</span>
                </div>

                <div class="flex gap-2 border-b border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-500 dark:border-white/10 dark:bg-gray-950 dark:text-gray-400">
                    <button type="button" class="cursor-pointer rounded-md px-2 py-1 font-medium hover:bg-white hover:text-gray-950 dark:hover:bg-gray-800 dark:hover:text-gray-100" x-on:click="setPaletteQuery('')">
                        All
                    </button>
                    <button type="button" class="cursor-pointer rounded-md px-2 py-1 font-medium hover:bg-white hover:text-gray-950 dark:hover:bg-gray-800 dark:hover:text-gray-100" x-on:click="setPaletteQuery(@js($tool['name'] . ' > '))">
                        {{ $tool['name'] }}
                    </button>
                    @if($apps->isNotEmpty())
                        <button type="button" class="cursor-pointer rounded-md px-2 py-1 font-medium hover:bg-white hover:text-gray-950 dark:hover:bg-gray-800 dark:hover:text-gray-100" x-on:click="setPaletteQuery(@js('Chief Tools > '))">
                            Chief Tools
                        </button>
                    @endif
                </div>

                <div x-ref="paletteItems" class="flex max-h-96 flex-col overflow-y-auto p-1.5">
                    @foreach($menuItems as $item)
                        @php($label = $item['text'] ?? $item['label'] ?? '')
                        <a href="{{ $item['href'] ?? '#' }}"
                           class="group flex items-center gap-3 rounded-md px-3 py-2 text-gray-700 transition hover:bg-gray-50 hover:text-gray-950 data-[active=true]:bg-gray-50 data-[active=true]:text-gray-950 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100 dark:data-[active=true]:bg-gray-800 dark:data-[active=true]:text-gray-100"
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
                                <span class="grid size-7 place-items-center rounded-md bg-gray-50 text-gray-500 dark:bg-gray-800 dark:text-gray-300">
                                    <i class="fa-fw {{ $item['icon'] }} text-sm"></i>
                                </span>
                            @endif
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium">{{ $label }}</span>
                                <span class="block truncate text-xs text-gray-500 dark:text-gray-400">{{ $tool['name'] }} &gt; {{ $label }}</span>
                            </span>
                            <i class="fa fa-fw fa-chevron-right text-xs text-gray-300 group-hover:text-gray-500 dark:text-gray-500 dark:group-hover:text-gray-300"></i>
                        </a>
                    @endforeach

                    @foreach($commands as $command)
                        <a href="{{ $command['href'] }}"
                           class="group flex items-center gap-3 rounded-md px-3 py-2 text-gray-700 transition hover:bg-gray-50 hover:text-gray-950 data-[active=true]:bg-gray-50 data-[active=true]:text-gray-950 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100 dark:data-[active=true]:bg-gray-800 dark:data-[active=true]:text-gray-100"
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
                            <span class="grid size-7 place-items-center rounded-md bg-gray-50 text-gray-500 dark:bg-gray-800 dark:text-gray-300">
                                <i class="fa-fw {{ $command['icon'] }} text-sm"></i>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium">{{ $command['label'] }}</span>
                                <span class="block truncate text-xs text-gray-500">
                                    <span class="dark:text-gray-400">{{ $command['category'] }} &gt; {{ $command['description'] ?: $command['label'] }}</span>
                                </span>
                            </span>
                            <i class="fa fa-fw fa-chevron-right text-xs text-gray-300 group-hover:text-gray-500 dark:text-gray-500 dark:group-hover:text-gray-300"></i>
                        </a>
                    @endforeach

                    @foreach($apps as $app)
                        <a href="{{ $app['href'] }}"
                           class="group flex items-center gap-3 rounded-md px-3 py-2 text-gray-700 transition hover:bg-gray-50 hover:text-gray-950 data-[active=true]:bg-gray-50 data-[active=true]:text-gray-950 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100 dark:data-[active=true]:bg-gray-800 dark:data-[active=true]:text-gray-100"
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
                                    <span class="block truncate text-xs text-gray-500 dark:text-gray-400">Chief Tools &gt; {{ $app['name'] }} · {{ $app['description'] }}</span>
                                @else
                                    <span class="block truncate text-xs text-gray-500 dark:text-gray-400">Chief Tools &gt; {{ $app['name'] }}</span>
                                @endif
                            </span>
                            <i class="fa fa-fw fa-arrow-up-right text-xs text-gray-300 group-hover:text-gray-500 dark:text-gray-500 dark:group-hover:text-gray-300"></i>
                        </a>
                    @endforeach

                    <template x-for="result in remoteResults" x-bind:key="result.id">
                        <a x-bind:href="result.url || '#'"
                           class="group flex items-center gap-3 rounded-md px-3 py-2 text-gray-700 transition hover:bg-gray-50 hover:text-gray-950 data-[active=true]:bg-gray-50 data-[active=true]:text-gray-950 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100 dark:data-[active=true]:bg-gray-800 dark:data-[active=true]:text-gray-100"
                           data-shell-command
                           x-bind:data-shell-category="result.category || ''"
                           x-bind:data-shell-title="result.title || ''"
                           x-bind:data-shell-body="result.description || ''"
                           x-bind:style="{ order: result.order || 9500 }"
                           x-bind:target="result.target || null"
                           x-bind:rel="result.target === '_blank' ? 'noopener' : null"
                           x-on:mouseenter="activeIndex = visiblePaletteItems().indexOf($el); syncPaletteActive()"
                           x-on:click="closePalette()">
                            <span class="relative grid size-7 place-items-center overflow-hidden rounded-md bg-gray-50 text-gray-500 dark:bg-gray-800 dark:text-gray-300">
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
                                <span class="block truncate text-xs text-gray-500 dark:text-gray-400">
                                    <span x-text="result.subtitle || result.category"></span>
                                    <template x-if="result.description">
                                        <span> · <span x-text="result.description"></span></span>
                                    </template>
                                </span>
                            </span>
                            <i class="fa fa-fw fa-chevron-right text-xs text-gray-300 group-hover:text-gray-500 dark:text-gray-500 dark:group-hover:text-gray-300"></i>
                        </a>
                    </template>

                    <div x-cloak x-show="remoteError" class="rounded-md px-3 py-2 text-xs text-red-600 dark:text-red-400">
                        Search results could not be loaded.
                    </div>

                    <div x-cloak
                         x-show="hasPaletteSearchTerm() && !remoteLoading && !remoteError && !hasPaletteItems()"
                         class="rounded-md px-3 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                        No results found.
                    </div>
                </div>

                <div class="flex items-center gap-4 border-t border-gray-200 bg-gray-50 px-4 py-2 text-xs text-gray-500 dark:border-white/10 dark:bg-gray-950 dark:text-gray-400">
                    <span class="flex items-center gap-1">
                        <span class="rounded border border-gray-200 bg-white px-1.5 py-0.5 font-mono text-[10px] dark:border-white/10 dark:bg-gray-900">↑</span>
                        <span class="rounded border border-gray-200 bg-white px-1.5 py-0.5 font-mono text-[10px] dark:border-white/10 dark:bg-gray-900">↓</span> move
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="rounded border border-gray-200 bg-white px-1.5 py-0.5 font-mono text-[10px] dark:border-white/10 dark:bg-gray-900">Enter</span> open
                    </span>
                    @if($apps->isNotEmpty())
                        <a href="{{ $allAppsUrl }}" target="_blank" rel="noopener" class="ml-auto inline-flex items-center gap-1 font-medium text-gray-600 hover:text-gray-950 dark:text-gray-300 dark:hover:text-gray-100">
                            <span>All apps</span>
                            <i class="fa fa-fw fa-arrow-up-right-from-square text-[10px] text-gray-400 dark:text-gray-400"></i>
                        </a>
                    @else
                        <span class="ml-auto">Use &gt; to scope results</span>
                    @endif
                </div>
            </div>
        </div>
    @endif
</nav>

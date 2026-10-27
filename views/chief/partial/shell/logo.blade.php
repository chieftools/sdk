{{--
    The app's logo and name, home, with the app switcher next to it. In the sidebar (`rail`) it spans the width, with the
    switcher's chevron on the right like the team menu's; collapsed, just the logo remains.
--}}
@php($rail = $rail ?? false)
<div @class(['relative flex items-center', 'w-full group-data-[shell-sidebar=collapsed]/chief:w-auto' => $rail])>
    <a href="{{ $tool['href'] }}"
       @class([
           'text flex h-10 items-center px-1 pr-2 text-fg transition hover:bg-surface-2',
           'gap-3' => empty($toolLogoUrl) && !empty($tool['brandIcon']),
           'gap-2' => !empty($toolLogoUrl) || empty($tool['brandIcon']),
           'rounded-l-lg' => config('chief.shell.app_switcher') && config('chief.shell.command_palette'),
           'min-w-0 flex-1 group-data-[shell-sidebar=collapsed]/chief:flex-none group-data-[shell-sidebar=collapsed]/chief:rounded-lg group-data-[shell-sidebar=collapsed]/chief:pr-1' => $rail,
           'rounded-lg' => !(config('chief.shell.app_switcher') && config('chief.shell.command_palette')),
       ])>
        <span class="grid size-9 shrink-0 place-items-center rounded-md text-accent">
            @if(!empty($toolLogoUrl))
                <img src="{{ $toolLogoUrl }}" alt="" class="max-h-8 max-w-8">
            @elseif(!empty($tool['brandIcon']))
                <span class="fa-stack text-[1rem] leading-none">
                    <i class="fad {{ $tool['icon'] }} fa-stack-2x"></i>
                    <i class="fab {{ $tool['brandIcon'] }} fa-stack-1x" style="font-size: 8px;"></i>
                </span>
            @else
                <i class="fad fa-fw {{ $tool['icon'] }} text-2xl"></i>
            @endif
        </span>
        <span @class(['block max-w-48 truncate text-sm font-semibold', 'group-data-[shell-sidebar=collapsed]/chief:hidden' => $rail])>{{ $tool['title'] ?? $tool['name'] }}</span>
    </a>
    @if(config('chief.shell.app_switcher') && config('chief.shell.command_palette'))
        <button type="button"
                @class(['flex h-10 cursor-pointer items-center rounded-r-lg px-1.5 text-fg transition hover:bg-surface-2', 'group-data-[shell-sidebar=collapsed]/chief:hidden' => $rail])
                x-on:click="togglePalette('switcher')"
                x-bind:aria-expanded="paletteOpen.toString()"
                aria-haspopup="dialog"
                aria-label="{{ __('chief::ui.shell.switch_app') }}"
                data-toggle="tooltip"
                data-title="{{ __('chief::ui.shell.switch_app') }} (⌘J)">
            <i class="fa fa-fw fa-chevron-down text-[10px] text-fg-faint"></i>
        </button>
    @endif
</div>

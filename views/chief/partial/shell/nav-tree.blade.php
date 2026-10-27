@php
    /**
     * The menu as a vertical list, for the sidebar layout and the mobile drawer. Items take optional keys besides those
     * of the top bar: `group`, which keeps neighbouring items with the same value together with a line between groups;
     * `group_label`, a heading instead of that line, set on a group's first item; and `children`, items shown under
     * their parent and opened while one of them is active. `touch` draws larger rows for the drawer.
     *
     * In the sidebar's collapsed rail only the icons remain: labels show on hover, a badge becomes a dot, headings
     * become lines like the other groups, and children are reached through their parent's page.
     *
     * @var \Illuminate\Support\Collection<int, array<string, mixed>> $menuItems
     * @var bool|null $touch
     */
    $touch = $touch ?? false;
    $previousGroup = null;
    $rowClasses = static fn (bool $active, bool $child = false): array => [
        'group relative flex items-center rounded-md font-medium transition outline-none',
        $touch ? 'min-h-12 gap-3 px-3 py-3 text-base' : ($child ? 'h-8 gap-2 px-2.5 text-[13px]' : 'h-9 gap-2.5 px-3 text-sm'),
        'bg-surface-2 text-fg' => $active,
        'text-fg-muted hover:bg-surface-2 hover:text-fg focus-visible:bg-surface-2 focus-visible:text-fg' => !$active,
        // The accent bar echoes the underline of the active item in the top bar
        'before:absolute before:inset-y-2 before:-left-2 before:w-0.5 before:rounded-full before:bg-accent' => $active && !$touch && !$child,
    ];
    $iconClasses = static fn (string $icon, bool $active): array => [
        'fa-fw shrink-0',
        $touch ? 'text-base' : 'text-[13px]',
        $icon,
        'text-accent' => $active,
        'text-fg-faint group-hover:text-fg-muted' => !$active,
    ];
    $railHidden = 'group-data-[shell-sidebar=collapsed]/chief:hidden';
    $railLabel = static fn (string $label): string => $touch ? '' : 'x-on:mouseenter="showRailTip($el)" x-on:mouseleave="hideRailTip()" x-on:focus="showRailTip($el)" x-on:blur="hideRailTip()" data-shell-label="' . e($label) . '"';
    $badgeDot = static function (array $item) use ($touch): string {
        $badge = $item['badge'] ?? null;

        if ($touch || $badge === null || $badge === '' || $badge === 0) {
            return '';
        }

        $tone = ($item['badge_tone'] ?? null) === 'danger' ? 'bg-red-500 dark:bg-red-400' : 'bg-accent';

        return '<span class="absolute top-1.5 left-6 hidden size-1.5 rounded-full group-data-[shell-sidebar=collapsed]/chief:block ' . $tone . '" aria-hidden="true"></span>';
    };
    $itemAttributes = static fn (array $item): string => implode(' ', array_filter([
        !empty($item['wire']) ? 'wire:navigate' : null,
        !empty($item['target']) ? 'target="' . e($item['target']) . '"' : null,
        ($item['target'] ?? null) === '_blank' ? 'rel="noopener"' : null,
    ]));
@endphp

@foreach($menuItems as $index => $item)
    @php
        $label = $item['text'] ?? $item['label'] ?? '';
        $group = $item['group'] ?? null;
        $children = collect($item['children'] ?? [])->values();
        $childActive = $children->contains(fn (array $child): bool => (bool)($child['active'] ?? false));
        $active = (bool)($item['active'] ?? false) && !$childActive;
        $expanded = $childActive || ($item['active'] ?? false);
    @endphp

    @if($loop->first || $group !== $previousGroup)
        @if(!empty($item['group_label']))
            <div @class([
                'px-3 pb-1 text-[11px] font-semibold uppercase tracking-wide text-fg-faint',
                'pt-5' => !$loop->first,
                'pt-1' => $loop->first,
                $railHidden => !$touch,
            ])>{{ $item['group_label'] }}</div>
            @if(!$touch && !$loop->first)
                <div class="mx-1 my-2 hidden border-t border-line group-data-[shell-sidebar=collapsed]/chief:block"></div>
            @endif
        @elseif(!$loop->first)
            <div class="mx-3 my-2 border-t border-line group-data-[shell-sidebar=collapsed]/chief:mx-1"></div>
        @endif
    @endif
    @php($previousGroup = $group)

    @if($children->isEmpty())
        <a href="{{ $item['href'] ?? '#' }}"
           @class($rowClasses($active))
           @if($active) aria-current="page" @endif
           {!! $itemAttributes($item) !!}
           {!! $railLabel($label) !!}
           @if($touch) x-on:click="menuOpen = false" @endif>
            @if(!empty($item['icon']))
                <i @class($iconClasses($item['icon'], $active))></i>
            @endif
            <span @class(['min-w-0 flex-1 truncate', $railHidden => !$touch])>{{ $label }}</span>
            <span @class(['contents', $railHidden => !$touch])>@include('chief::partial.shell.nav-badge', ['item' => $item])</span>
            {!! $badgeDot($item) !!}
        </a>
    @else
        {{-- Opened while a child is active; otherwise it remembers being opened for the rest of the session --}}
        @php($groupKey = 'chief-shell-menu:' . \Illuminate\Support\Str::slug($label))
        <div x-data="{ open: {{ $expanded ? 'true' : 'false' }} || sessionStorage.getItem(@js($groupKey)) === '1' }"
             x-init="$watch('open', open => sessionStorage.setItem(@js($groupKey), open ? '1' : '0'))">
            <div class="flex items-center">
                @if(!empty($item['href']))
                    <a href="{{ $item['href'] }}"
                       @class([...$rowClasses($active), 'min-w-0 flex-1', 'text-fg!' => $childActive])
                       @if($active) aria-current="page" @endif
                       {!! $itemAttributes($item) !!}
                       {!! $railLabel($label) !!}
                       @if($touch) x-on:click="menuOpen = false" @endif>
                        @if(!empty($item['icon']))
                            <i @class($iconClasses($item['icon'], $active || $childActive))></i>
                        @endif
                        <span @class(['min-w-0 flex-1 truncate', $railHidden => !$touch])>{{ $label }}</span>
                        <span @class(['contents', $railHidden => !$touch])>@include('chief::partial.shell.nav-badge', ['item' => $item])</span>
                        {!! $badgeDot($item) !!}
                    </a>
                @else
                    <button type="button"
                            @class([...$rowClasses(false), 'min-w-0 flex-1 cursor-pointer text-left', 'text-fg!' => $childActive])
                            x-on:click="open = !open"
                            x-bind:aria-expanded="open.toString()"
                            {!! $railLabel($label) !!}>
                        @if(!empty($item['icon']))
                            <i @class($iconClasses($item['icon'], $childActive))></i>
                        @endif
                        <span @class(['min-w-0 flex-1 truncate', $railHidden => !$touch])>{{ $label }}</span>
                        <span @class(['contents', $railHidden => !$touch])>@include('chief::partial.shell.nav-badge', ['item' => $item])</span>
                        {!! $badgeDot($item) !!}
                    </button>
                @endif
                <button type="button"
                        @class([
                            'grid shrink-0 cursor-pointer place-items-center rounded-md text-fg-faint transition hover:bg-surface-2 hover:text-fg-muted',
                            $touch ? 'size-12' : 'size-8',
                            $railHidden => !$touch,
                        ])
                        x-on:click="open = !open"
                        x-bind:aria-expanded="open.toString()"
                        aria-label="{{ $label }}">
                    <i class="fa fa-fw fa-chevron-right text-[10px] transition-transform" x-bind:class="open && 'rotate-90'"></i>
                </button>
            </div>

            <div x-show="open" @unless($expanded) x-cloak @endunless @class(['mt-0.5 space-y-0.5 border-l border-line', $touch ? 'ml-6 pl-3' : 'ml-[1.3rem] pl-2', $railHidden => !$touch])>
                @foreach($children as $child)
                    @php($childIsActive = (bool)($child['active'] ?? false))
                    <a href="{{ $child['href'] ?? '#' }}"
                       @class($rowClasses($childIsActive, child: true))
                       @if($childIsActive) aria-current="page" @endif
                       {!! $itemAttributes($child) !!}
                       @if($touch) x-on:click="menuOpen = false" @endif>
                        @if(!empty($child['icon']))
                            <i @class($iconClasses($child['icon'], $childIsActive))></i>
                        @endif
                        <span class="min-w-0 flex-1 truncate">{{ $child['text'] ?? $child['label'] ?? '' }}</span>
                        @include('chief::partial.shell.nav-badge', ['item' => $child])
                    </a>
                @endforeach
            </div>
        </div>
    @endif
@endforeach

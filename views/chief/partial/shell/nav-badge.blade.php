@php
    // An optional count or short text after a menu item's label, such as open alerts. Empty values show nothing;
    // `badge_tone` "danger" draws attention, and `badge_label` tells screen readers what the badge counts.
    $badge = $item['badge'] ?? null;
    $badgeLabel = $item['badge_label'] ?? null;
@endphp

@if($badge !== null && $badge !== '' && $badge !== 0)
    <span @class([
            'inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full px-1.5 text-[11px] font-semibold tabular-nums',
            'bg-red-500/15 text-red-700 dark:bg-red-400/15 dark:text-red-300' => ($item['badge_tone'] ?? null) === 'danger',
            'bg-accent/15 text-accent' => ($item['badge_tone'] ?? null) !== 'danger',
        ])
        @if($badgeLabel) title="{{ $badgeLabel }}" @endif
    ><span @if($badgeLabel) aria-hidden="true" @endif>{{ $badge }}</span>@if($badgeLabel)<span class="sr-only">{{ $badgeLabel }}</span>@endif</span>
@endif

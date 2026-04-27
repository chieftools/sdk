@php
    $active = $item['active'] ?? false;
    $href = $item['href'] ?? '#';
    $icon = $item['icon'] ?? null;
    $label = $item['text'] ?? $item['label'] ?? '';
@endphp

<a href="{{ $href }}"
   @class([
       'group inline-flex h-full shrink-0 items-center gap-2 border-b-2 px-3 text-sm font-medium transition',
       'border-accent text-fg' => $active,
       'border-transparent text-fg-muted hover:text-fg' => !$active,
   ])
   @if(!empty($item['vue-href'])) vue-href="{{ $item['vue-href'] }}" @endif
   @if(!empty($item['wire'])) wire:navigate @endif
   @if(!empty($item['target'])) target="{{ $item['target'] }}" @endif
   @if(!empty($item['target']) && $item['target'] === '_blank') rel="noopener" @endif
>
    @if($icon)
        <i @class([
            'fa-fw text-[13px]',
            $icon,
            'text-accent' => $active,
            'text-fg-subtle group-hover:text-fg-muted' => !$active,
        ])></i>
    @endif

    <span class="whitespace-nowrap">{{ $label }}</span>
</a>

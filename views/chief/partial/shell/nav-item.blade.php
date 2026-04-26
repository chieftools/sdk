@php
    $active = $item['active'] ?? false;
    $href = $item['href'] ?? '#';
    $icon = $item['icon'] ?? null;
    $label = $item['text'] ?? $item['label'] ?? '';
@endphp

<a href="{{ $href }}"
   @class([
       'group inline-flex h-full shrink-0 items-center gap-2 border-b-2 px-3 text-sm font-medium transition',
       'border-[var(--chief-shell-accent)] text-gray-950 dark:text-gray-100' => $active,
       'border-transparent text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100' => !$active,
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
            'text-[var(--chief-shell-accent)]' => $active,
            'text-gray-400 group-hover:text-gray-500 dark:text-gray-400 dark:group-hover:text-gray-200' => !$active,
        ])></i>
    @endif

    <span class="whitespace-nowrap">{{ $label }}</span>
</a>

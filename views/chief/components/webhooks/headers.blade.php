@props([
    'headers' => [],
    // Folds the headers away behind a summary, for responses whose many headers would push the body down
    'collapsible' => false,
])
@php($headers = $headers ?? [])
@if($collapsible && $headers !== [])
    <details {{ $attributes->class('group') }}>
        <summary class="flex cursor-pointer list-none items-center gap-1.5 px-5 py-1.5 text-xs text-fg-subtle select-none hover:text-fg [&::-webkit-details-marker]:hidden">
            <flux:icon.chevron-right variant="micro" class="transition-transform group-open:rotate-90" />
            <span class="font-medium text-fg">Headers</span>
            <span>({{ count($headers) }})</span>
        </summary>
        <dl class="grid gap-x-6 gap-y-1 border-t border-line px-5 py-3 font-mono text-xs sm:grid-cols-[auto_minmax(0,1fr)]">
            @foreach($headers as $name => $value)
                <dt class="text-fg-subtle">{{ $name }}</dt>
                <dd class="break-all text-fg max-sm:mb-1">{{ $value }}</dd>
            @endforeach
        </dl>
    </details>
@elseif($headers === [])
    <p {{ $attributes->class('px-5 py-3 text-xs text-fg-subtle italic') }}>No headers</p>
@else
    <dl {{ $attributes->class('grid gap-x-6 gap-y-1 px-5 py-3 font-mono text-xs sm:grid-cols-[auto_minmax(0,1fr)]') }}>
        @foreach($headers as $name => $value)
            <dt class="text-fg-subtle">{{ $name }}</dt>
            <dd class="break-all text-fg max-sm:mb-1">{{ $value }}</dd>
        @endforeach
    </dl>
@endif

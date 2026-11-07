@props([
    'body' => null,
    'label' => null,
    'truncated' => false,
    'empty' => 'Empty',
])
{{-- Tailwind generates the highlight classes from here, BodyFormatter writes them: text-sky-700 dark:text-sky-300 text-emerald-700 dark:text-emerald-300 text-purple-700 dark:text-purple-300 text-amber-700 dark:text-amber-300 --}}
<div {{ $attributes->class('border-t border-line bg-surface-2') }} x-data>
    <div class="flex items-center gap-2 px-5 py-1.5 text-xs text-fg-subtle">
        @if($label)
            <span class="font-medium text-fg">{{ $label }}</span>
        @endif
        @if($truncated)
            <span>· cut off at {{ \Illuminate\Support\Number::fileSize(\ChiefTools\SDK\Webhook\Outbound\Deliverer::MAX_RESPONSE_BODY_BYTES) }}</span>
        @endif
        @if($body !== null && $body !== '')
            <button type="button" class="ms-auto inline-flex items-center gap-1 hover:text-fg" x-on:click="navigator.clipboard.writeText(@js(\ChiefTools\SDK\Webhook\Outbound\Support\BodyFormatter::pretty($body))).then(() => $flux.toast({ text: 'Copied to the clipboard.', variant: 'success' }))">
                <flux:icon.clipboard-document variant="micro" />Copy
            </button>
        @endif
    </div>
    <pre class="max-h-96 overflow-auto border-t border-line px-5 py-3 font-mono text-xs/5 whitespace-pre text-fg">@if($body === null || $body === '')<span class="text-fg-subtle italic">{{ $empty }}</span>@else{{ \ChiefTools\SDK\Webhook\Outbound\Support\BodyFormatter::highlight($body) }}@endif</pre>
</div>

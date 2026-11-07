<div @if($polls) wire:poll.3s @endif>
    <div class="flex items-center gap-2 border-b border-line py-2 ps-5 pe-2">
        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
            <flux:heading class="font-mono">{{ $delivery->event->type }}</flux:heading>
            <flux:badge size="sm" :color="$delivery->status->asColor()" inset="top bottom">{{ $delivery->status->asLabel() }}</flux:badge>
        </div>
        @if($delivery->endpoint->isEnabled())
            <flux:button size="sm" icon="arrow-path" wire:click="resend" wire:confirm="Send this event to the endpoint again now? It is added as the next attempt.">Resend</flux:button>
        @endif
        <flux:modal.close>
            <flux:button size="sm" variant="ghost" icon="x-mark" aria-label="Close" />
        </flux:modal.close>
    </div>

    <dl class="grid gap-x-6 gap-y-1.5 px-5 py-3 text-xs sm:grid-cols-[auto_minmax(0,1fr)_auto_minmax(0,1fr)]">
        <dt class="text-fg-subtle">Event</dt>
        <dd class="font-mono break-all text-fg max-sm:mb-1">{{ $delivery->event->formatted_id }}</dd>
        <dt class="text-fg-subtle">Delivery</dt>
        <dd class="font-mono break-all text-fg max-sm:mb-1">{{ $delivery->formatted_id }}</dd>
        <dt class="text-fg-subtle">Created</dt>
        <dd class="text-fg max-sm:mb-1">{{ $delivery->created_at->isoFormat('MMM D, YYYY HH:mm:ss') }}</dd>
        <dt class="text-fg-subtle">{{ $delivery->isPending() && $delivery->attempts > 0 ? 'Next retry' : 'Attempts' }}</dt>
        <dd class="text-fg max-sm:mb-1">
            @if($delivery->isPending() && $delivery->attempts > 0 && $delivery->next_attempt_at)
                {{ $delivery->next_attempt_at->diffForHumans() }}
            @else
                {{ $delivery->attempts }}
            @endif
        </dd>
    </dl>

    @if($delivery->attemptLog->isEmpty())
        <p class="flex items-center gap-2 border-t border-line px-5 py-3 text-xs text-fg-subtle">
            @if($delivery->isPending())
                <flux:icon.arrow-path variant="micro" class="shrink-0 animate-spin text-blue-600 dark:text-blue-400" />
                Sending the first attempt…
            @else
                No attempts were made, the endpoint was disabled before it.
            @endif
        </p>
    @else
        <div class="grid border-t border-line md:grid-cols-[13rem_minmax(0,1fr)]">
            {{-- The attempts, newest first --}}
            <ol class="max-h-[32rem] overflow-y-auto border-line max-md:border-b md:border-e">
                @foreach($delivery->attemptLog->reverse() as $item)
                    <li wire:key="attempt-{{ $item->id }}">
                        <button type="button" wire:click="showAttempt('{{ $item->id }}')" @class([
                            'flex w-full items-start gap-2 px-4 py-2.5 text-start text-xs hover:bg-zinc-50 dark:hover:bg-white/5',
                            'bg-zinc-100 dark:bg-white/10' => $attempt?->id === $item->id,
                        ])>
                            <span @class(['mt-1 size-2 shrink-0 rounded-full', 'bg-green-500' => $item->succeeded, 'bg-red-500' => !$item->succeeded])></span>
                            <span class="min-w-0">
                                <span class="block font-medium text-fg">
                                    Attempt {{ $item->number }}@if($item->manual) <span class="font-normal text-fg-subtle">(resent)</span>@endif ·
                                    @if($item->response_status !== null)
                                        <span class="font-mono">{{ $item->response_status }}</span>
                                    @else
                                        {{ $item->error?->asLabel() ?? 'Failed' }}
                                    @endif
                                </span>
                                <span class="block text-fg-subtle">{{ $item->attempted_at->isoFormat('MMM D, HH:mm:ss') }}@if($item->duration_ms !== null) · {{ number_format($item->duration_ms) }} ms @endif</span>
                            </span>
                        </button>
                    </li>
                @endforeach
            </ol>

            <div class="min-w-0">
                @if($attempt)
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 px-5 py-2.5 text-xs">
                        <span class="text-fg-subtle">POST <span class="font-mono text-fg">{{ $delivery->endpoint->url }}</span></span>
                        @if($attempt->error_message)
                            <span @class(['font-mono break-all', 'text-red-600 dark:text-red-400' => !$attempt->succeeded])>{{ $attempt->error_message }}</span>
                        @endif
                    </div>

                    <div class="border-t border-line px-5 pt-2">
                        <flux:tabs wire:model.live="tab" variant="segmented" size="sm">
                            <flux:tab name="response">Response</flux:tab>
                            <flux:tab name="request">Request</flux:tab>
                        </flux:tabs>
                    </div>

                    @if($tab === 'request')
                        <x-chief::webhooks.headers :headers="$attempt->request_headers" class="mt-2 border-t border-line" />
                        <x-chief::webhooks.code-block label="Body" :body="$delivery->event->payload" />
                    @else
                        <div class="mt-2 flex items-center gap-3 border-t border-line px-5 py-2.5 text-xs">
                            @if($attempt->response_status !== null)
                                <span @class(['font-mono font-medium', 'text-green-700 dark:text-green-400' => $attempt->succeeded, 'text-red-700 dark:text-red-400' => !$attempt->succeeded])>HTTP {{ $attempt->response_status }}</span>
                            @else
                                <span class="font-medium text-red-700 dark:text-red-400">No response</span>
                            @endif
                            @if($attempt->duration_ms !== null)
                                <span class="text-fg-subtle">{{ number_format($attempt->duration_ms) }} ms</span>
                            @endif
                        </div>
                        @if($attempt->response_status !== null)
                            <x-chief::webhooks.code-block label="Body" :body="$attempt->response_body" :truncated="$attempt->response_truncated" />
                            <x-chief::webhooks.headers :headers="$attempt->response_headers ?? []" collapsible class="border-t border-line bg-surface-2" />
                        @endif
                    @endif
                @endif
            </div>
        </div>
    @endif
</div>

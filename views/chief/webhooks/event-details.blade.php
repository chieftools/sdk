<div>
    <div class="flex items-center gap-2 border-b border-line py-2 ps-5 pe-2">
        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
            <flux:heading class="font-mono">{{ $event->type }}</flux:heading>
            <span class="font-mono text-xs text-fg-subtle">{{ $event->formatted_id }}</span>
            <span class="text-xs text-fg-subtle" title="{{ $event->created_at->toDayDateTimeString() }}">· {{ $event->created_at->isoFormat('MMM D, YYYY HH:mm:ss') }}</span>
        </div>
        <flux:modal.close>
            <flux:button size="sm" variant="ghost" icon="x-mark" aria-label="Close" />
        </flux:modal.close>
    </div>

    <div class="bg-surface-2 px-5 py-1.5 text-xs font-medium text-fg">Deliveries</div>
    <div class="divide-y divide-line border-t border-line">
        @foreach($endpoints as $endpoint)
            @php($deliveries = $event->deliveries->where('endpoint_id', $endpoint->id))
            <div class="flex flex-col gap-2 px-5 py-2.5 text-xs sm:flex-row sm:items-center sm:gap-4" wire:key="event-endpoint-{{ $endpoint->id }}">
                <div class="min-w-0 flex-1">
                    <a href="{{ route('webhooks.endpoint', ['endpointId' => $endpoint->formatted_id]) }}" wire:navigate class="block truncate font-mono text-fg hover:underline">{{ $endpoint->url }}</a>
                </div>
                <div class="flex items-center gap-2">
                    @forelse($deliveries as $delivery)
                        <flux:badge size="sm" :color="$delivery->status->asColor()" wire:key="event-delivery-{{ $delivery->id }}">{{ $delivery->status->asLabel() }}</flux:badge>
                    @empty
                        <span class="text-fg-subtle">Not sent</span>
                    @endforelse
                    @if($endpoint->isEnabled())
                        <flux:button size="xs" variant="subtle" icon="paper-airplane" wire:click="resend('{{ $endpoint->formatted_id }}')" wire:confirm="{{ $deliveries->isEmpty() ? 'Send' : 'Resend' }} this event to {{ $endpoint->url }}?">{{ $deliveries->isEmpty() ? 'Send' : 'Resend' }}</flux:button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <x-chief::webhooks.code-block label="Payload" :body="$event->payload" />
</div>

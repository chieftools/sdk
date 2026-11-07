<x-slot name="title">Events · Webhooks</x-slot>

<div wire:poll.30s>
    <x-tw::page-header :breadcrumbs="$breadcrumbs">
        <i class="fad fa-fw fa-satellite-dish text-fg-faint"></i>
        Events
    </x-tw::page-header>

    <div class="mb-3 flex flex-wrap items-center gap-2">
        <flux:select wire:model.live="type" class="w-auto! min-w-56" aria-label="Event type">
            <flux:select.option value="">Any event</flux:select.option>
            @foreach($eventGroups as $types)
                @foreach($types as $eventType)
                    <flux:select.option :value="$eventType->value">{{ $eventType->value }}</flux:select.option>
                @endforeach
            @endforeach
            <flux:select.option :value="\ChiefTools\SDK\Webhook\Outbound\Enums\BuiltInEvent::TEST->value">{{ \ChiefTools\SDK\Webhook\Outbound\Enums\BuiltInEvent::TEST->value }}</flux:select.option>
        </flux:select>
        @if($type !== '')
            <button type="button" wire:click="$set('type', '')" class="text-sm font-medium text-accent-content hover:underline">Show all</button>
        @endif
    </div>

    <flux:card variant="muted" body="inset" size="sm">
        <flux:card.body>
            <flux:table bleed>
                <flux:table.columns>
                    <flux:table.column>Event</flux:table.column>
                    <flux:table.column>Deliveries</flux:table.column>
                    <flux:table.column>Created</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse($events as $event)
                        <flux:table.row :key="'event-' . $event->id" class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-white/5" wire:click="showEvent('{{ $event->formatted_id }}')">
                            <flux:table.cell class="whitespace-normal">
                                <div class="font-mono text-sm text-fg">{{ $event->type }}</div>
                                <div class="font-mono text-xs text-fg-subtle">{{ $event->formatted_id }}</div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex flex-wrap gap-1">
                                    @if($event->succeeded_count > 0)
                                        <flux:badge size="sm" color="green">{{ $event->succeeded_count }} succeeded</flux:badge>
                                    @endif
                                    @if($event->failed_count > 0)
                                        <flux:badge size="sm" color="red">{{ $event->failed_count }} failed</flux:badge>
                                    @endif
                                    @if($event->pending_count > 0)
                                        <flux:badge size="sm" color="amber">{{ $event->pending_count }} pending</flux:badge>
                                    @endif
                                    @if($event->deliveries_count === 0)
                                        <span class="text-xs text-fg-subtle">None</span>
                                    @endif
                                </div>
                            </flux:table.cell>
                            <flux:table.cell class="whitespace-nowrap">
                                <span title="{{ $event->created_at->toDayDateTimeString() }}">{{ $event->created_at->diffForHumans() }}</span>
                                <div class="text-xs text-fg-subtle">{{ $event->created_at->isoFormat('MMM D, HH:mm:ss') }}</div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="3" class="text-fg-subtle">
                                {{ $type !== '' ? 'No events of this type.' : 'No events yet. Events are only kept for teams with an endpoint that receives them.' }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card.body>
        @if($events->hasPages())
            <flux:card.footer>
                <flux:pagination :paginator="$events" class="border-t-0! pt-0!" />
            </flux:card.footer>
        @endif
    </flux:card>

    <flux:text size="sm" class="mt-3">Events are kept for {{ \ChiefTools\SDK\Webhook\Outbound\WebhookConfig::retentionDays() }} days.</flux:text>

    <flux:modal name="webhook-event" :closable="false" class="w-full max-w-3xl overflow-hidden p-0!">
        @if($detailsEventId)
            <livewire:chief-webhooks.event-details :event-id="$detailsEventId" :key="'event-details-' . $detailsEventId" />
        @endif
    </flux:modal>
</div>

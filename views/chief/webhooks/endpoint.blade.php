@php
    use ChiefTools\SDK\Webhook\Outbound\Enums\BuiltInEvent;
    use ChiefTools\SDK\Webhook\Outbound\Enums\DeliveryStatus;
    use ChiefTools\SDK\Webhook\Outbound\Enums\DisabledReason;

    $host = parse_url($endpoint->url, PHP_URL_HOST) ?: $endpoint->url;
    $axisFormat = $days ? ['month' => 'short', 'day' => 'numeric'] : ['hour' => '2-digit', 'minute' => '2-digit', 'hour12' => false];
    $tooltipFormat = $days ? ['month' => 'short', 'day' => 'numeric'] : ['month' => 'short', 'day' => 'numeric', 'hour' => '2-digit', 'minute' => '2-digit', 'hour12' => false];
@endphp

<x-slot name="title">{{ $host }} · Webhooks</x-slot>

<div>
    <x-tw::page-header :breadcrumbs="$breadcrumbs">
        <i class="fad fa-fw fa-satellite-dish text-fg-faint"></i>
        <span class="font-mono">{{ $host }}</span>
        <x-slot:actions>
            <div class="flex flex-wrap gap-2">
                @if($endpoint->isEnabled())
                    <flux:button size="sm" variant="primary" icon="paper-airplane" wire:click="sendTestEvent">Send test event</flux:button>
                @endif
                <flux:button size="sm" icon="pencil-square" wire:click="edit">Edit</flux:button>
                <flux:dropdown position="bottom" align="end">
                    <flux:button size="sm" icon="ellipsis-horizontal" aria-label="More actions" />
                    <flux:menu>
                        @if($endpoint->isEnabled())
                            <flux:menu.item icon="pause-circle" wire:click="disable" wire:confirm="Disable this endpoint? It receives no events until you enable it again, and its pending deliveries are cancelled. You can resend those from its delivery log.">Disable</flux:menu.item>
                        @else
                            <flux:menu.item icon="play-circle" wire:click="enable">Enable</flux:menu.item>
                        @endif
                        <flux:menu.item icon="arrow-path" wire:click="confirmRotateSecret">Rotate secret</flux:menu.item>
                        <flux:menu.separator />
                        <flux:menu.item icon="trash" variant="danger" wire:click="delete" wire:confirm="Delete this endpoint and its delivery log? It stops receiving events right away.">Delete</flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </x-slot:actions>
    </x-tw::page-header>

    @if(!$endpoint->isEnabled())
        <flux:callout variant="secondary" icon="pause-circle" class="mb-4">
            <flux:callout.heading>
                {{ $endpoint->disabled_reason?->asLabel() ?? 'Disabled' }}
                @if($endpoint->disabled_at)
                    <span class="font-normal text-fg-subtle">· {{ $endpoint->disabled_at->diffForHumans() }}</span>
                @endif
            </flux:callout.heading>
            <flux:callout.text>
                @if($endpoint->disabled_reason === DisabledReason::FAILING)
                    Every delivery failed for {{ \ChiefTools\SDK\Webhook\Outbound\WebhookConfig::disableAfterDays() }} days, so it stopped receiving events.
                @endif
                It receives no events until you enable it, and events that happen meanwhile are not sent to it later. Deliveries that were pending when it was disabled are cancelled, resend those from the log below. An event the team's other endpoints received can be sent to it from the <a href="{{ route('webhooks.events') }}" wire:navigate class="font-medium underline">Events page</a>.
            </flux:callout.text>
            <x-slot name="actions">
                <flux:button size="sm" wire:click="enable">Enable</flux:button>
            </x-slot>
        </flux:callout>
    @elseif($endpoint->failing_since !== null)
        <flux:callout variant="danger" icon="exclamation-triangle" class="mb-4">
            <flux:callout.heading>Failing since {{ $endpoint->failing_since->diffForHumans() }}</flux:callout.heading>
            <flux:callout.text>
                Deliveries keep failing and are retried for about three days. If every delivery fails for {{ \ChiefTools\SDK\Webhook\Outbound\WebhookConfig::disableAfterDays() }} days, the endpoint is disabled.
            </flux:callout.text>
        </flux:callout>
    @endif

    <div class="grid gap-4 lg:grid-cols-3">
        <flux:card class="lg:col-span-2">
            <dl class="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-[auto_minmax(0,1fr)]">
                <dt class="text-fg-subtle">URL</dt>
                <dd class="break-all font-mono text-fg">{{ $endpoint->url }}</dd>

                <dt class="text-fg-subtle">Description</dt>
                <dd class="text-fg">{{ $endpoint->description ?? '—' }}</dd>

                <dt class="text-fg-subtle">Status</dt>
                <dd><flux:badge size="sm" :color="$endpoint->status->asColor()">{{ $endpoint->status->asLabel() }}</flux:badge></dd>

                <dt class="text-fg-subtle">Events</dt>
                <dd class="flex flex-wrap gap-1">
                    @if($endpoint->receivesAllEvents())
                        <span class="text-fg">All events</span>
                    @else
                        @foreach($endpoint->events as $type)
                            <flux:badge size="sm" class="font-mono" wire:key="subscribed-{{ $type }}">{{ $type }}</flux:badge>
                        @endforeach
                    @endif
                </dd>

                <dt class="text-fg-subtle">ID</dt>
                <dd class="font-mono text-xs/5 text-fg-subtle">{{ $endpoint->formatted_id }}</dd>
            </dl>
        </flux:card>

        <flux:card>
            <flux:heading>Signing secret</flux:heading>
            <flux:text size="sm" class="mt-1">Verify that a request came from us by checking its <span class="font-mono text-xs">webhook-signature</span> header with this secret.</flux:text>

            <div class="mt-3">
                @if($revealedSecret !== null)
                    <flux:input class="font-mono" :value="$revealedSecret" readonly variant="filled" copyable aria-label="Signing secret" />
                @else
                    <div class="flex gap-2">
                        <flux:input class="font-mono" value="whsec_••••••••••••••••••••••••••••••••" readonly variant="filled" aria-label="Signing secret, hidden" />
                        <flux:button wire:click="revealSecret">Reveal</flux:button>
                    </div>
                @endif
            </div>

            @if($endpoint->previous_secret !== null && $endpoint->previous_secret_expires_at?->isFuture())
                <flux:text size="sm" class="mt-2">The previous secret also signs deliveries until {{ $endpoint->previous_secret_expires_at->isoFormat('MMM D, HH:mm') }}.</flux:text>
            @endif
        </flux:card>
    </div>

    <div class="mt-6 mb-3 flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="lg">Activity</flux:heading>
        <flux:radio.group wire:model.live="range" variant="segmented" size="sm">
            <flux:radio value="24h" label="24 hours" />
            <flux:radio value="7d" label="7 days" />
            <flux:radio value="30d" label="30 days" />
        </flux:radio.group>
    </div>

    <div class="grid gap-4 lg:grid-cols-2" wire:poll.30s>
        <flux:card wire:key="deliveries-chart-{{ $range }}">
            <flux:heading>Delivery attempts</flux:heading>
            <div class="mt-1 flex flex-wrap items-baseline gap-x-2">
                <span class="text-2xl font-semibold tabular-nums text-zinc-800 dark:text-white">{{ $summary['success_rate'] === null ? '—' : $summary['success_rate'] . '%' }}</span>
                <flux:text size="sm">succeeded of {{ number_format($summary['attempts']) }} {{ str('attempt')->plural($summary['attempts']) }}</flux:text>
            </div>

            <flux:chart :value="$chart" class="mt-3">
                <flux:chart.viewport class="aspect-3/1">
                    <flux:chart.svg>
                        <flux:chart.stack width="70%">
                            <flux:chart.bar field="succeeded" class="text-green-500 dark:text-green-400" />
                            <flux:chart.bar field="failed" class="text-red-500 dark:text-red-400" radius="2 0" />
                        </flux:chart.stack>

                        <flux:chart.axis axis="x" field="date" :format="$axisFormat" tick-count="6">
                            <flux:chart.axis.line />
                            <flux:chart.axis.tick />
                        </flux:chart.axis>

                        <flux:chart.axis axis="y" :format="['notation' => 'compact', 'maximumFractionDigits' => 0]" tick-count="4">
                            <flux:chart.axis.grid />
                            <flux:chart.axis.tick />
                        </flux:chart.axis>

                        <flux:chart.cursor type="area" />
                    </flux:chart.svg>
                </flux:chart.viewport>

                <flux:chart.tooltip>
                    <flux:chart.tooltip.heading field="date" :format="$tooltipFormat" />
                    <flux:chart.tooltip.value field="succeeded" label="Succeeded" />
                    <flux:chart.tooltip.value field="failed" label="Failed" />
                </flux:chart.tooltip>

                <div class="flex justify-center gap-4 pt-3">
                    <flux:chart.legend label="Succeeded">
                        <flux:chart.legend.indicator class="bg-green-500 dark:bg-green-400" />
                    </flux:chart.legend>
                    <flux:chart.legend label="Failed">
                        <flux:chart.legend.indicator class="bg-red-500 dark:bg-red-400" />
                    </flux:chart.legend>
                </div>
            </flux:chart>
        </flux:card>

        <flux:card wire:key="latency-chart-{{ $range }}">
            <flux:heading>Response time</flux:heading>
            <div class="mt-1 flex flex-wrap items-baseline gap-x-2">
                <span class="text-2xl font-semibold tabular-nums text-zinc-800 dark:text-white">{{ $summary['avg_ms'] === null ? '—' : number_format($summary['avg_ms']) . ' ms' }}</span>
                <flux:text size="sm">on average</flux:text>
            </div>

            <flux:chart :value="$chart" class="mt-3">
                <flux:chart.viewport class="aspect-3/1">
                    <flux:chart.svg>
                        <flux:chart.group width="70%">
                            <flux:chart.bar field="avg_ms" class="text-blue-600 dark:text-blue-400" />
                            <flux:chart.bar field="max_ms" class="text-blue-300 dark:text-sky-200" />
                        </flux:chart.group>

                        <flux:chart.axis axis="x" field="date" :format="$axisFormat" tick-count="6">
                            <flux:chart.axis.line />
                            <flux:chart.axis.tick />
                        </flux:chart.axis>

                        <flux:chart.axis axis="y" :format="['notation' => 'compact', 'maximumFractionDigits' => 0]" tick-suffix=" ms" tick-count="4">
                            <flux:chart.axis.grid />
                            <flux:chart.axis.tick />
                        </flux:chart.axis>

                        <flux:chart.cursor type="area" />
                    </flux:chart.svg>
                </flux:chart.viewport>

                <flux:chart.tooltip>
                    <flux:chart.tooltip.heading field="date" :format="$tooltipFormat" />
                    <flux:chart.tooltip.value field="avg_ms" label="Average" suffix=" ms" />
                    <flux:chart.tooltip.value field="max_ms" label="Slowest" suffix=" ms" />
                </flux:chart.tooltip>

                <div class="flex justify-center gap-4 pt-3">
                    <flux:chart.legend label="Average">
                        <flux:chart.legend.indicator class="bg-blue-600 dark:bg-blue-400" />
                    </flux:chart.legend>
                    <flux:chart.legend label="Slowest">
                        <flux:chart.legend.indicator class="bg-blue-300 dark:bg-sky-200" />
                    </flux:chart.legend>
                </div>
            </flux:chart>
        </flux:card>
    </div>

    <div class="mt-6 mb-3 flex flex-wrap items-center gap-2">
        <flux:heading size="lg" class="me-auto">Deliveries</flux:heading>
        <flux:select wire:model.live="status" class="w-auto! min-w-36" aria-label="Status">
            <flux:select.option value="">Any status</flux:select.option>
            @foreach(DeliveryStatus::cases() as $option)
                <flux:select.option :value="$option->value">{{ $option->asLabel() }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="event" class="w-auto! min-w-48" aria-label="Event">
            <flux:select.option value="">Any event</flux:select.option>
            @foreach($eventGroups as $types)
                @foreach($types as $type)
                    <flux:select.option :value="$type->value">{{ $type->value }}</flux:select.option>
                @endforeach
            @endforeach
            <flux:select.option :value="BuiltInEvent::TEST->value">{{ BuiltInEvent::TEST->value }}</flux:select.option>
        </flux:select>
    </div>

    @if($filtered)
        <flux:text size="sm" class="mb-3">
            {{ number_format($deliveries->total()) }} {{ str('delivery')->plural($deliveries->total()) }} match.
            <button type="button" wire:click="resetFilters" class="font-medium text-accent-content hover:underline">Show all</button>
        </flux:text>
    @endif

    <flux:card variant="muted" body="inset" size="sm" wire:poll.10s>
        <flux:card.body>
            <flux:table bleed>
                <flux:table.columns>
                    <flux:table.column class="w-28">Status</flux:table.column>
                    <flux:table.column>Event</flux:table.column>
                    <flux:table.column class="hidden sm:table-cell">Response</flux:table.column>
                    <flux:table.column class="hidden md:table-cell">Attempts</flux:table.column>
                    <flux:table.column>Created</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse($deliveries as $delivery)
                        <flux:table.row :key="'delivery-' . $delivery->id" class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-white/5" wire:click="showDelivery('{{ $delivery->formatted_id }}')">
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$delivery->status->asColor()">{{ $delivery->status->asLabel() }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="whitespace-normal">
                                <div class="font-mono text-sm text-fg">{{ $delivery->event->type }}</div>
                                <div class="font-mono text-xs text-fg-subtle">{{ $delivery->event->formatted_id }}</div>
                            </flux:table.cell>
                            <flux:table.cell class="hidden sm:table-cell">
                                @if($delivery->last_response_status !== null)
                                    <span @class(['font-mono tabular-nums', 'text-green-700 dark:text-green-400' => $delivery->last_response_status < 300, 'text-red-700 dark:text-red-400' => $delivery->last_response_status >= 300])>{{ $delivery->last_response_status }}</span>
                                @elseif($delivery->attempts > 0)
                                    <span class="text-red-700 dark:text-red-400">No response</span>
                                @else
                                    <span class="text-fg-subtle">—</span>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="hidden whitespace-nowrap md:table-cell">
                                <span class="tabular-nums">{{ $delivery->attempts }}</span>
                                @if($delivery->isPending() && $delivery->attempts > 0 && $delivery->next_attempt_at)
                                    <div class="text-xs text-fg-subtle">next {{ $delivery->next_attempt_at->diffForHumans() }}</div>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="whitespace-nowrap">
                                <span title="{{ $delivery->created_at->toDayDateTimeString() }}">{{ $delivery->created_at->diffForHumans() }}</span>
                                <div class="text-xs text-fg-subtle">{{ $delivery->created_at->isoFormat('MMM D, HH:mm:ss') }}</div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="text-fg-subtle">
                                {{ $filtered ? 'No deliveries match.' : 'No deliveries yet. Send a test event to check the endpoint receives them.' }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card.body>
        @if($deliveries->hasPages())
            <flux:card.footer>
                <flux:pagination :paginator="$deliveries" class="border-t-0! pt-0!" />
            </flux:card.footer>
        @endif
    </flux:card>

    <flux:text size="sm" class="mt-3">Deliveries and their attempts are kept for {{ \ChiefTools\SDK\Webhook\Outbound\WebhookConfig::retentionDays() }} days.</flux:text>

    <flux:modal name="webhook-endpoint" class="w-full max-w-2xl">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">Edit endpoint</flux:heading>

            @include('chief::webhooks.partial.endpoint-form')

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="webhook-rotate-secret" class="w-full max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Rotate the signing secret?</flux:heading>
                <flux:text class="mt-2">The endpoint gets a new secret. Deliveries are signed with both the current and the new secret for 24 hours, so you can update your receiver without it rejecting any.</flux:text>
            </div>

            <flux:error name="secret" />

            @if($endpoint->hasActivePreviousSecret())
                <flux:callout variant="warning" icon="exclamation-triangle">
                    <flux:callout.heading>This stops the previous secret right away</flux:callout.heading>
                    <flux:callout.text>
                        You rotated this secret {{ $endpoint->previous_secret_expires_at->copy()->subHours(\ChiefTools\SDK\Webhook\Outbound\Actions\RotateSecret::DEFAULT_OVERLAP_HOURS)->diffForHumans() }}, and the secret from before still signs deliveries until {{ $endpoint->previous_secret_expires_at->isoFormat('MMM D, HH:mm') }}. Only two secrets sign at a time, so rotating again stops that one now. Receivers that still use it will reject every delivery.
                    </flux:callout.text>
                </flux:callout>
            @endif

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button :variant="$endpoint->hasActivePreviousSecret() ? 'danger' : 'primary'" wire:click="rotateSecret({{ $endpoint->hasActivePreviousSecret() ? 'true' : 'false' }})">{{ $endpoint->hasActivePreviousSecret() ? 'Rotate anyway' : 'Rotate secret' }}</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="webhook-delivery" :closable="false" class="w-full max-w-4xl overflow-hidden p-0!">
        @if($detailsDeliveryId)
            <livewire:chief-webhooks.delivery-details :delivery-id="$detailsDeliveryId" :key="'delivery-details-' . $detailsDeliveryId" />
        @endif
    </flux:modal>
</div>

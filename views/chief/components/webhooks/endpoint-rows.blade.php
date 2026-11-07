@props([
    'endpoints',
])
{{-- The rows of an endpoint list, shared by the webhooks page and any app page that lists the endpoints --}}
@php
    $recent = app(\ChiefTools\SDK\Webhook\Outbound\Stats\EndpointStats::class)->lastDay($endpoints);
    // Endpoints without attempts still get the day's empty sparkline, so the lists stay aligned
    $since      = now()->setTimezone(config('app.timezone'))->startOfHour()->subHours(23);
    $emptyHours = array_map(static fn (int $index): array => ['hour' => $since->copy()->addHours($index), 'succeeded' => 0, 'failed' => 0], range(0, 23));
@endphp
<div {{ $attributes->class('divide-y divide-line') }}>
    @foreach($endpoints as $endpoint)
        @php($stats = $recent[$endpoint->id] ?? null)
        <a href="{{ route('webhooks.endpoint', ['endpointId' => $endpoint->formatted_id]) }}" wire:navigate wire:key="endpoint-{{ $endpoint->id }}" class="flex flex-col gap-2 px-(--flux-bleed-x) py-3 hover:bg-zinc-50 sm:flex-row sm:items-center sm:gap-4 dark:hover:bg-white/5">
            <div class="min-w-0 flex-1">
                <div class="truncate font-mono text-sm font-medium text-fg">{{ $endpoint->url }}</div>
                <div class="text-xs text-fg-subtle">
                    @if($endpoint->description)
                        {{ $endpoint->description }} ·
                    @endif
                    {{ $endpoint->receivesAllEvents() ? 'All events' : count($endpoint->events) . ' ' . str('event')->plural(count($endpoint->events)) }}
                </div>
            </div>
            <div class="flex items-center gap-3 text-xs">
                @if($endpoint->isEnabled())
                    <x-chief::webhooks.sparkline :hours="$stats['hours'] ?? $emptyHours" class="max-sm:hidden" />
                    @if($stats !== null)
                        <span title="In the last 24 hours" @class(['tabular-nums', 'text-red-600 dark:text-red-400' => $stats['success_rate'] < 90, 'text-fg-subtle' => $stats['success_rate'] >= 90])><span class="font-medium">{{ $stats['success_rate'] }}%</span> of {{ number_format($stats['attempts']) }} {{ str('attempt')->plural($stats['attempts']) }} succeeded</span>
                    @else
                        <span class="text-fg-subtle" title="In the last 24 hours">No attempts</span>
                    @endif
                @endif
                @if($endpoint->isEnabled() && $endpoint->failing_since !== null)
                    <flux:badge size="sm" color="red">Failing</flux:badge>
                @else
                    <flux:badge size="sm" :color="$endpoint->status->asColor()">{{ $endpoint->status->asLabel() }}</flux:badge>
                @endif
            </div>
        </a>
    @endforeach
</div>

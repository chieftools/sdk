{{--
    The team's webhook endpoints on an app's settings page, managed on the webhooks pages.

    @param \ChiefTools\SDK\Entities\Team $team
    @param string $description what the endpoints receive, like "Send signed requests to your own systems when servers go offline"
--}}
@props(['team', 'description'])

@php
    $endpoints = ChiefTools\SDK\Webhook\Outbound\Models\Endpoint::query()->where('team_id', $team->getKey())->oldest()->get();
    $limit     = $team->webhookEndpointsLimit();
@endphp

<div id="webhooks">
    <flux:card body="inset" variant="soft" size="lg" {{ $attributes }}>
        <flux:card.header>
            <flux:card.heading>Webhooks</flux:card.heading>
            <flux:card.subheading>{{ $description }}</flux:card.subheading>

            <flux:card.actions class="my-0 self-center">
                @if($endpoints->isNotEmpty())
                    <flux:button size="sm" variant="subtle" :href="route('webhooks.events', ['team_hint' => $team->slug])" wire:navigate>Events</flux:button>
                    <flux:button size="sm" :href="route('webhooks', ['team_hint' => $team->slug])" wire:navigate>Manage webhooks</flux:button>
                @elseif($limit !== 0)
                    <flux:button size="sm" icon="plus" :href="route('webhooks', ['team_hint' => $team->slug])" wire:navigate>Add endpoint</flux:button>
                @endif
            </flux:card.actions>
        </flux:card.header>

        <flux:card.body>
            <flux:card.bleed>
                @if($endpoints->isNotEmpty())
                    <x-chief::webhooks.endpoint-rows :endpoints="$endpoints" :team-hint="$team->slug" />
                @elseif($limit === 0)
                    <div class="px-(--flux-bleed-x) py-4 text-sm text-fg-subtle">Webhooks are not included in your plan.</div>
                @else
                    <div class="px-(--flux-bleed-x) py-4 text-sm text-fg-subtle">No endpoints yet.</div>
                @endif
            </flux:card.bleed>
        </flux:card.body>
    </flux:card>
</div>

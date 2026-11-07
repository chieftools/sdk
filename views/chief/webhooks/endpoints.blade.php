<x-slot name="title">Webhooks</x-slot>

<div>
    <x-tw::page-header :breadcrumbs="$breadcrumbs">
        <i class="fad fa-fw fa-satellite-dish text-fg-faint"></i>
        Webhooks
        <x-slot:actions>
            <div class="flex gap-2">
                <flux:button size="sm" variant="subtle" icon="queue-list" :href="route('webhooks.events')" wire:navigate>Events</flux:button>
                @if($limit !== 0)
                    <flux:button size="sm" icon="plus" wire:click="create">Add endpoint</flux:button>
                @endif
            </div>
        </x-slot:actions>
    </x-tw::page-header>

    <flux:card body="inset" variant="soft" size="lg">
        <flux:card.header>
            <flux:card.heading>Endpoints</flux:card.heading>
            <flux:card.subheading>
                URLs that receive a signed POST request when something happens. Failed deliveries are retried for about three days.
                @if($limit > 0)
                    Your plan includes {{ $limit }} {{ str('endpoint')->plural($limit) }}.
                @endif
            </flux:card.subheading>
        </flux:card.header>

        <flux:card.body>
            <flux:card.bleed>
                @if($limit === 0)
                    <div class="px-(--flux-bleed-x) py-4 text-sm text-fg-subtle">Webhooks are not included in your plan.</div>
                @elseif($endpoints->isEmpty())
                    <div class="px-(--flux-bleed-x) py-4 text-sm text-fg-subtle">
                        No endpoints yet. Add one to receive events as they happen, then send it a test event to check it verifies them.
                    </div>
                @else
                    <x-chief::webhooks.endpoint-rows :endpoints="$endpoints" />
                @endif
            </flux:card.bleed>
        </flux:card.body>
    </flux:card>

    <flux:text size="sm" class="mt-3">
        Every request is signed following the <a href="https://www.standardwebhooks.com" target="_blank" rel="noopener" class="font-medium text-accent-content hover:underline">Standard Webhooks</a> specification, so you can verify it with one of its libraries.
    </flux:text>

    <flux:modal name="webhook-endpoint" class="w-full max-w-2xl">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">Add endpoint</flux:heading>
                <flux:text class="mt-1">Its signing secret is shown right after you add it, and can be revealed again on its page.</flux:text>
            </div>

            @include('chief::webhooks.partial.endpoint-form')

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Add endpoint</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

<?php

namespace ChiefTools\SDK\Webhook\Outbound\Livewire;

use Flux\Flux;
use Livewire\Component;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use ChiefTools\SDK\Webhook\Outbound\Models\Event;
use ChiefTools\SDK\Webhook\Outbound\Models\Endpoint;
use ChiefTools\SDK\Webhook\Outbound\Actions\ResendEvent;
use ChiefTools\SDK\Webhook\Outbound\Livewire\Concerns\ManagesTeamWebhooks;

/**
 * One event in a modal: its payload and its delivery to each endpoint.
 */
class EventDetails extends Component
{
    use ManagesTeamWebhooks;

    #[Locked]
    public string $eventId;

    public function mount(string $eventId): void
    {
        $this->eventId = $this->findEvent($eventId)->formatted_id;
    }

    public function resend(string $endpointId, ResendEvent $resendEvent): void
    {
        $resendEvent->handle($this->findEvent($this->eventId), $this->findEndpoint($endpointId));

        Flux::toast(text: 'It is sent with the same event ID, as the next attempt of its delivery to the endpoint.', heading: 'Event sent', variant: 'success');
    }

    public function render(): View
    {
        $event = $this->findEvent($this->eventId)->load(['deliveries' => static fn ($query) => $query->with('endpoint')->latest()]);

        return view('chief::webhooks.event-details', [
            'event'     => $event,
            'endpoints' => Endpoint::query()->where('team_id', $event->team_id)->oldest()->get(),
        ]);
    }
}

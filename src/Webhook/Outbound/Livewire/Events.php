<?php

namespace ChiefTools\SDK\Webhook\Outbound\Livewire;

use Flux\Flux;
use Livewire\Component;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use Livewire\Attributes\Locked;
use ChiefTools\SDK\Webhook\Outbound\Webhooks;
use ChiefTools\SDK\Webhook\Outbound\Models\Event;
use ChiefTools\SDK\Webhook\Outbound\Enums\DeliveryStatus;
use ChiefTools\SDK\Webhook\Outbound\Livewire\Concerns\ManagesTeamWebhooks;

/**
 * Every event sent to the team's endpoints, with how its deliveries went.
 */
class Events extends Component
{
    use ManagesTeamWebhooks, WithPagination;

    public const int PER_PAGE = 50;

    #[Url(except: '')]
    public string $type = '';

    /** The event the details modal shows. */
    #[Locked]
    public ?string $detailsEventId = null;

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function showEvent(string $eventId): void
    {
        $this->detailsEventId = $this->findEvent($eventId)->formatted_id;

        Flux::modal('webhook-event')->show();
    }

    public function render(Webhooks $webhooks): View
    {
        $events = Event::query()
            ->select(['id', 'team_id', 'type', 'created_at'])
            ->where('team_id', $this->webhookTeam()->getKey())
            ->when($this->type !== '', fn ($query) => $query->where('type', $this->type))
            ->withCount([
                'deliveries',
                'deliveries as succeeded_count' => static fn ($query) => $query->where('status', DeliveryStatus::SUCCEEDED),
                'deliveries as failed_count'    => static fn ($query) => $query->where('status', DeliveryStatus::FAILED),
                'deliveries as pending_count'   => static fn ($query) => $query->where('status', DeliveryStatus::PENDING),
            ])
            ->latest()
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE);

        return view('chief::webhooks.events', [
            'events'      => $events,
            'eventGroups' => $webhooks->groupedEventTypes(),
            'breadcrumbs' => $this->webhookBreadcrumbs([['title' => 'Events']]),
        ]);
    }
}

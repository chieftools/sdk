<?php

namespace ChiefTools\SDK\Webhook\Outbound\Livewire;

use Flux\Flux;
use Livewire\Component;
use Illuminate\View\View;
use ChiefTools\SDK\Webhook\Outbound\Webhooks;
use ChiefTools\SDK\Webhook\Outbound\Models\Endpoint;
use ChiefTools\SDK\Webhook\Outbound\Actions\CreateEndpoint;
use ChiefTools\SDK\Webhook\Outbound\Livewire\Concerns\ManagesTeamWebhooks;

/**
 * The team's webhook endpoints, with how their deliveries did over the last day, and adding one.
 */
class Endpoints extends Component
{
    use ChoosesEvents, ManagesTeamWebhooks;

    /** The session key that makes the endpoint page show the secret of an endpoint that was just added. */
    public const string REVEAL_SECRET_SESSION_KEY = 'chief-webhooks.reveal-secret';

    public string $url = '';

    public string $description = '';

    public function create(): void
    {
        $this->reset('url', 'description');
        $this->resetEventChoice();
        $this->resetValidation();

        Flux::modal('webhook-endpoint')->show();
    }

    public function save(CreateEndpoint $createEndpoint): void
    {
        $endpoint = $createEndpoint->handle($this->webhookTeam(), [
            'url'         => $this->url,
            'description' => $this->description,
            'events'      => $this->chosenEvents(),
        ]);

        session()->flash(self::REVEAL_SECRET_SESSION_KEY, $endpoint->id);

        $this->redirectRoute('webhooks.endpoint', ['endpointId' => $endpoint->formatted_id], navigate: true);
    }

    public function render(Webhooks $webhooks): View
    {
        $team      = $this->webhookTeam();
        $endpoints = Endpoint::query()->where('team_id', $team->getKey())->oldest()->get();

        return view('chief::webhooks.endpoints', [
            'endpoints'   => $endpoints,
            'eventGroups' => $webhooks->groupedEventTypes(),
            'limit'       => $team->webhookEndpointsLimit(),
            'breadcrumbs' => $this->webhookBreadcrumbs(),
        ]);
    }
}

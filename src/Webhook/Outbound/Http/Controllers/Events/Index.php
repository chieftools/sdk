<?php

namespace ChiefTools\SDK\Webhook\Outbound\Http\Controllers\Events;

use Dedoc\Scramble\Attributes\Group;
use ChiefTools\SDK\Webhook\Outbound\Models\Event;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use ChiefTools\SDK\Webhook\Outbound\Http\Resources\EventResource;
use ChiefTools\SDK\Webhook\Outbound\Http\Requests\ListEventsRequest;
use ChiefTools\SDK\Webhook\Outbound\Http\Concerns\ResolvesWebhookTeam;

#[Group('Webhooks')]
class Index
{
    use ResolvesWebhookTeam;

    /**
     * List webhook events.
     *
     * List the events sent to the team's endpoints, newest first. Events are only kept when an endpoint received them, for 30 days.
     *
     * @operationId webhooks.events.index
     */
    public function __invoke(ListEventsRequest $request): AnonymousResourceCollection
    {
        $events = Event::query()
            ->where('team_id', $this->webhookTeam()->getKey())
            ->when($request->has('type'), static fn ($query) => $query->where('type', $request->string('type')->toString()))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($request->perPage());

        return EventResource::collection($events);
    }
}

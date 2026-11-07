<?php

namespace ChiefTools\SDK\Webhook\Outbound\Http\Concerns;

use ChiefTools\SDK\Entities\Team;
use ChiefTools\SDK\Entities\User;
use Illuminate\Support\Facades\Gate;
use ChiefTools\SDK\Webhook\Outbound\Models\Event;
use ChiefTools\SDK\Webhook\Outbound\Models\Delivery;
use ChiefTools\SDK\Webhook\Outbound\Models\Endpoint;

/**
 * The webhooks of the team an API token acts for: the team of a team token, or the current team of a personal token.
 * Every lookup goes through that team, so IDs of other teams are not found. A personal token also needs the
 * `manageWebhooks` gate when the app defines it, like the dashboard.
 */
trait ResolvesWebhookTeam
{
    protected function webhookTeam(): Team
    {
        /** @var \ChiefTools\SDK\Entities\User|\ChiefTools\SDK\Entities\Team|null $entity */
        $entity = auth()->user();

        $team = match (true) {
            $entity instanceof Team => $entity,
            $entity instanceof User => $entity->team,
            default                 => null,
        };

        abort_if($team === null, 401, 'Missing authenticated team.');

        if ($entity instanceof User && Gate::has('manageWebhooks')) {
            Gate::authorize('manageWebhooks', $team);
        }

        return $team;
    }

    /** @throws \Illuminate\Database\Eloquent\ModelNotFoundException */
    protected function findEndpoint(string $id): Endpoint
    {
        return Endpoint::query()->where('team_id', $this->webhookTeam()->getKey())->whereFormattedId($id)->firstOrFail();
    }

    /** @throws \Illuminate\Database\Eloquent\ModelNotFoundException */
    protected function findDelivery(string $id): Delivery
    {
        return Delivery::query()->where('team_id', $this->webhookTeam()->getKey())->whereFormattedId($id)->firstOrFail();
    }

    /** @throws \Illuminate\Database\Eloquent\ModelNotFoundException */
    protected function findEvent(string $id): Event
    {
        return Event::query()->where('team_id', $this->webhookTeam()->getKey())->whereFormattedId($id)->firstOrFail();
    }
}

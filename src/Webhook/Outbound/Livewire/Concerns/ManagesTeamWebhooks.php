<?php

namespace ChiefTools\SDK\Webhook\Outbound\Livewire\Concerns;

use ChiefTools\SDK\Entities\Team;
use Illuminate\Support\Facades\Gate;
use ChiefTools\SDK\Webhook\Outbound\Models\Event;
use ChiefTools\SDK\Webhook\Outbound\WebhookConfig;
use ChiefTools\SDK\Webhook\Outbound\Models\Delivery;
use ChiefTools\SDK\Webhook\Outbound\Models\Endpoint;

/**
 * The current team's webhooks for the dashboard. Every lookup goes through the team, so another team's IDs are not
 * found. Apps that limit who manages webhooks define the `manageWebhooks` gate, it receives the user and the team.
 */
trait ManagesTeamWebhooks
{
    protected function webhookTeam(): Team
    {
        $team = authenticated_user_or_fail()->team;

        abort_if($team === null, 404);

        if (Gate::has('manageWebhooks')) {
            Gate::authorize('manageWebhooks', $team);
        }

        return $team;
    }

    protected function findEndpoint(string $id): Endpoint
    {
        return Endpoint::query()->where('team_id', $this->webhookTeam()->getKey())->whereFormattedId($id)->firstOrFail();
    }

    protected function findDelivery(string $id): Delivery
    {
        return Delivery::query()->where('team_id', $this->webhookTeam()->getKey())->whereFormattedId($id)->firstOrFail();
    }

    protected function findEvent(string $id): Event
    {
        return Event::query()->where('team_id', $this->webhookTeam()->getKey())->whereFormattedId($id)->firstOrFail();
    }

    /**
     * The breadcrumbs above a webhooks page: the app's parent page, the webhooks overview and then the page itself.
     *
     * @param list<array{title: string, href?: string}> $trail
     *
     * @return list<array{title: string, href?: string, wire?: bool}>
     */
    protected function webhookBreadcrumbs(array $trail = []): array
    {
        $parent = WebhookConfig::breadcrumbParent();

        return array_values(array_filter([
            $parent === null ? null : ['title' => $parent['title'], 'href' => route($parent['route']), 'wire' => true],
            $trail === [] ? ['title' => 'Webhooks'] : ['title' => 'Webhooks', 'href' => route('webhooks'), 'wire' => true],
            ...array_map(static fn (array $crumb): array => [...$crumb, 'wire' => true], $trail),
        ]));
    }
}

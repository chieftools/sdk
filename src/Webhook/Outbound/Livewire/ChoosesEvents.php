<?php

namespace ChiefTools\SDK\Webhook\Outbound\Livewire;

use ChiefTools\SDK\Webhook\Outbound\Models\Endpoint;

/**
 * Choosing the events of an endpoint: every event, including ones added later, or a selection.
 */
trait ChoosesEvents
{
    /** `all` for every event, `selected` for the ones in `$events`. */
    public string $eventMode = 'all';

    /** @var list<string> */
    public array $events = [];

    protected function resetEventChoice(?Endpoint $endpoint = null): void
    {
        $this->eventMode = $endpoint === null || $endpoint->receivesAllEvents() ? 'all' : 'selected';
        $this->events    = $endpoint === null || $endpoint->receivesAllEvents() ? [] : array_values($endpoint->events);
    }

    /** @return list<string> */
    protected function chosenEvents(): array
    {
        return $this->eventMode === 'all' ? [Endpoint::ALL_EVENTS] : array_values($this->events);
    }
}

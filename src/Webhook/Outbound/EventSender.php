<?php

namespace ChiefTools\SDK\Webhook\Outbound;

use Closure;
use Throwable;
use ChiefTools\SDK\Webhook\Outbound\Models\Endpoint;
use ChiefTools\SDK\Webhook\Outbound\Contracts\WebhookEventType;

/**
 * The base of an app's webhook service: one public method per event that renders its payload and calls `send()`.
 *
 * Payloads render only when an endpoint receives the event, so call the methods for every change without checking
 * first. Render the resource as the API returns it at the time of the call, so call before a change is undone or a
 * record deleted.
 *
 * Webhooks and e-mail notifications must not depend on each other: call the webhook before the e-mail, a failure is
 * reported instead of thrown so the caller's change and its e-mail go ahead.
 *
 * `to()` sends the same events to one endpoint whether it subscribed or not, and throws what fails, for example events.
 */
abstract readonly class EventSender
{
    final public function __construct(
        protected Webhooks $webhooks,
        protected ?Endpoint $endpoint = null,
    ) {}

    /** Sends the events to this endpoint only, whether it subscribed to them or not, and throws what fails. */
    public function to(Endpoint $endpoint): static
    {
        return new static($this->webhooks, $endpoint);
    }

    /**
     * Sends the event to the team's subscribed endpoints, or to the one endpoint of `to()`, rendering the payload only
     * when it goes anywhere.
     *
     * @param \Closure(): (array<string, mixed>|\JsonSerializable) $render the API representation of what the event is about
     * @param array<string, mixed>                                 $data   more fields next to the object in `data`
     */
    protected function send(WebhookEventType $type, int $teamId, Closure $render, array $data = []): void
    {
        if ($this->endpoint !== null) {
            $this->webhooks->sendTo($this->endpoint, $type, $render(), $data);

            return;
        }

        try {
            if (!$this->webhooks->hasSubscribers($teamId, $type)) {
                return;
            }

            $this->webhooks->dispatch($teamId, $type, $render(), $data);
        } catch (Throwable $e) {
            report($e);
        }
    }
}

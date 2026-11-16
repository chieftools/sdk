<?php

namespace ChiefTools\SDK\Webhook\Outbound\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use ChiefTools\SDK\Entities\Team;
use Illuminate\Support\Facades\Route;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use ChiefTools\SDK\Webhook\Outbound\Deliverer;
use ChiefTools\SDK\Webhook\Outbound\WebhookConfig;
use ChiefTools\SDK\Webhook\Outbound\Models\Endpoint;

/**
 * An e-mail that tells a team member one of the team's webhook endpoints has a problem. Apps send it to the members
 * who want it when the SDK fires `EndpointFailing` or `EndpointDisabled`, with a link to their notification settings.
 *
 * Set an idempotency key so a retried send is the same message.
 */
abstract class EndpointProblem extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected ?string $idempotencyKey = null;

    public function __construct(
        public readonly Team $team,
        public readonly Endpoint $endpoint,
        public readonly ?string $notificationSettingsUrl = null,
    ) {}

    /** The markdown view of the e-mail. */
    abstract protected function markdownView(): string;

    public function setIdempotencyKey(string $idempotencyKey): static
    {
        $this->idempotencyKey = $idempotencyKey;

        return $this;
    }

    public function headers(): Headers
    {
        if ($this->idempotencyKey === null) {
            return new Headers;
        }

        return new Headers(
            messageId: hash('xxh128', $this->idempotencyKey) . '@' . str((string)config('mail.from.address'))->after('@'),
            text: ['Idempotency-Key' => $this->idempotencyKey],
        );
    }

    public function content(): Content
    {
        $endpointUrl  = route('webhooks.endpoint', ['team_hint' => $this->team->slug, 'endpointId' => $this->endpoint->formatted_id]);
        $dashboardUrl = Route::has('dashboard') ? route('dashboard', ['team_hint' => $this->team->slug]) : null;

        return new Content(
            markdown: $this->markdownView(),
            with: [
                'appName'          => (string)config('app.name'),
                'teamName'         => $this->team->name,
                'dashboardUrl'     => $dashboardUrl,
                'url'              => $this->endpoint->url,
                'failingSince'     => $this->endpoint->failing_since?->diffForHumans() ?? 'recently',
                'retryDays'        => max(1, (int)round(array_sum(WebhookConfig::retrySchedule()) / 86400)),
                'disableAfterDays' => WebhookConfig::disableAfterDays(),
                'timeoutSeconds'   => Deliverer::TIMEOUT_SECONDS,
                'endpointUrl'      => $endpointUrl,
                'quickLinks'       => array_values(array_filter([
                    ['title' => 'Endpoint', 'url' => $endpointUrl],
                    $dashboardUrl === null ? null : ['title' => 'Dashboard', 'url' => $dashboardUrl],
                    $this->notificationSettingsUrl === null ? null : ['title' => 'Notification settings', 'url' => $this->notificationSettingsUrl],
                ])),
            ],
        );
    }

    /** The endpoint's host for the subject, or its URL when it has none. */
    protected function host(): string
    {
        return parse_url($this->endpoint->url, PHP_URL_HOST) ?: $this->endpoint->url;
    }
}

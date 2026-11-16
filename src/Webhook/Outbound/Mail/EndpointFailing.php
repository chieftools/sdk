<?php

namespace ChiefTools\SDK\Webhook\Outbound\Mail;

use Illuminate\Mail\Mailables\Envelope;

/**
 * Tells a team member that deliveries to one of the team's endpoints keep failing, send it on `EndpointFailing`.
 */
class EndpointFailing extends EndpointProblem
{
    public function envelope(): Envelope
    {
        return new Envelope(subject: "⚠️ Webhooks to {$this->host()} are failing");
    }

    protected function markdownView(): string
    {
        return 'chief::webhooks.mail.endpoint-failing';
    }
}

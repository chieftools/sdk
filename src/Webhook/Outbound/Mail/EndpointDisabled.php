<?php

namespace ChiefTools\SDK\Webhook\Outbound\Mail;

use Illuminate\Mail\Mailables\Envelope;

/**
 * Tells a team member that one of the team's endpoints was disabled after failing for days, send it on `EndpointDisabled`.
 */
class EndpointDisabled extends EndpointProblem
{
    public function envelope(): Envelope
    {
        return new Envelope(subject: "🛑 Webhooks to {$this->host()} are disabled");
    }

    protected function markdownView(): string
    {
        return 'chief::webhooks.mail.endpoint-disabled';
    }
}

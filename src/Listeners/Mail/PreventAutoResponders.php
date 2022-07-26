<?php

namespace ChiefTools\SDK\Listeners\Mail;

use Illuminate\Mail\Events\MessageSending;

class PreventAutoResponders
{
    public function handle(MessageSending $event): void
    {
        $headers = $event->message->getHeaders();

        $headers->addTextHeader('Auto-submitted', 'auto-generated');
        $headers->addTextHeader('X-Auto-Response-Suppress', 'OOF, AutoReply');
    }
}

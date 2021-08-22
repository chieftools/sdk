<?php

namespace IronGate\Chief\Support\Mail;

use Swift_Events_SendEvent;
use Swift_Events_SendListener;

class PreventAutoRespondersTransportPlugin implements Swift_Events_SendListener
{
    public function beforeSendPerformed(Swift_Events_SendEvent $evt)
    {
        $message = $evt->getMessage();

        $message->getHeaders()->addTextHeader('Auto-submitted', 'auto-generated');
        $message->getHeaders()->addTextHeader('X-Auto-Response-Suppress', 'OOF, AutoReply');
    }

    public function sendPerformed(Swift_Events_SendEvent $evt)
    {
    }
}

<?php

namespace ChiefTools\SDK\Webhook\Outbound\Enums;

/**
 * What `\ChiefTools\SDK\Webhook\Outbound\Webhooks::record()` did with an event.
 */
enum RecordResult: string
{
    /** The event and a pending delivery for every endpoint that receives it were stored. */
    case RECORDED = 'recorded';
    /** An event with the same ID was stored before, nothing was stored again. */
    case ALREADY_RECORDED = 'already_recorded';
    /** No enabled endpoint of the team receives the event, or webhooks are off, so nothing was stored. */
    case NO_SUBSCRIBERS = 'no_subscribers';
}

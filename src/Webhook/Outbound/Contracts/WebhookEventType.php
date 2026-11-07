<?php

namespace ChiefTools\SDK\Webhook\Outbound\Contracts;

use BackedEnum;

/**
 * An event endpoints can subscribe to, apps list theirs as cases of a backed enum named in `chief.webhooks.events`.
 * The value is the event type in payloads, like `server.offline`.
 */
interface WebhookEventType extends BackedEnum
{
    /** The heading the event is listed under when choosing events, like "Availability". */
    public function group(): string;

    /** What happened when the event is sent, like "A server stopped checking in.". */
    public function description(): string;
}

<?php

namespace Tests\Fixtures\Webhooks;

use ChiefTools\SDK\Webhook\Outbound\Attributes\WebhookObject;
use ChiefTools\SDK\Webhook\Outbound\Contracts\WebhookEventType;

#[WebhookObject('array{type: string, id: string}', 'A reference to the ship the event is about.')]
enum ReferenceEventType: string implements WebhookEventType
{
    case SHIP_DOCKED = 'ship.docked';

    #[WebhookObject(description: 'The ship as it was right before it sank.')]
    case SHIP_SUNK = 'ship.sunk';

    public function group(): string
    {
        return 'Ships';
    }

    public function description(): string
    {
        return match ($this) {
            self::SHIP_DOCKED => 'A ship docked.',
            self::SHIP_SUNK   => 'A ship sank.',
        };
    }
}

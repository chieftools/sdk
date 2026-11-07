<?php

namespace Tests\Fixtures\Webhooks;

use ChiefTools\SDK\Webhook\Outbound\Contracts\WebhookEventType;

enum ExampleEventType: string implements WebhookEventType
{
    case LANTERN_LIT    = 'lantern.lit';
    case LANTERN_DIMMED = 'lantern.dimmed';
    case HARBOR_FLOODED = 'harbor.flooded';

    public function group(): string
    {
        return match ($this) {
            self::LANTERN_LIT, self::LANTERN_DIMMED => 'Lanterns',
            self::HARBOR_FLOODED                    => 'Harbors',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::LANTERN_LIT    => 'A lantern was lit.',
            self::LANTERN_DIMMED => 'A lantern was dimmed.',
            self::HARBOR_FLOODED => 'A harbor flooded.',
        };
    }
}

<?php

namespace Tests\Fixtures\Webhooks;

use Carbon\CarbonInterface;
use ChiefTools\SDK\Webhook\Outbound\Attributes\WebhookData;
use ChiefTools\SDK\Webhook\Outbound\Attributes\WebhookObject;
use ChiefTools\SDK\Webhook\Outbound\Contracts\WebhookEventType;

enum ExampleEventType: string implements WebhookEventType
{
    #[WebhookObject(LanternResource::class)]
    #[WebhookData('lit_at', CarbonInterface::class, 'When it was lit.')]
    #[WebhookData('colors', 'list<LanternColor>', 'The colors it shines in.', required: false)]
    case LANTERN_LIT = 'lantern.lit';

    case LANTERN_DIMMED = 'lantern.dimmed';

    #[WebhookObject('array{id: string, depth: float}')]
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

<?php

namespace ChiefTools\SDK\Webhook\Outbound\Enums;

use Dedoc\Scramble\Attributes\SchemaName;
use ChiefTools\SDK\Scramble\Attributes\ExtensibleEnum;

#[ExtensibleEnum]
#[SchemaName('WebhookEndpointStatus')]
enum EndpointStatus: string
{
    /** Receives the events it subscribed to. */
    case ENABLED = 'enabled';
    /** Receives nothing until someone enables it again, `disabled_reason` tells why. */
    case DISABLED = 'disabled';

    public function asLabel(): string
    {
        return match ($this) {
            self::ENABLED  => 'Enabled',
            self::DISABLED => 'Disabled',
        };
    }

    public function asColor(): string
    {
        return match ($this) {
            self::ENABLED  => 'green',
            self::DISABLED => 'zinc',
        };
    }
}

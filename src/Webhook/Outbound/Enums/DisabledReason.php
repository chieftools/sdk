<?php

namespace ChiefTools\SDK\Webhook\Outbound\Enums;

use Dedoc\Scramble\Attributes\SchemaName;
use ChiefTools\SDK\Scramble\Attributes\ExtensibleEnum;

#[ExtensibleEnum]
#[SchemaName('WebhookEndpointDisabledReason')]
enum DisabledReason: string
{
    /** Someone disabled it. */
    case MANUAL = 'manual';
    /** Every delivery failed for five days, so it was disabled automatically. */
    case FAILING = 'failing';

    public function asLabel(): string
    {
        return match ($this) {
            self::MANUAL  => 'Disabled by a team member',
            self::FAILING => 'Disabled after failing for days',
        };
    }
}

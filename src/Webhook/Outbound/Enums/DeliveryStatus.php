<?php

namespace ChiefTools\SDK\Webhook\Outbound\Enums;

use Dedoc\Scramble\Attributes\SchemaName;
use ChiefTools\SDK\Scramble\Attributes\ExtensibleEnum;

#[ExtensibleEnum]
#[SchemaName('WebhookDeliveryStatus')]
enum DeliveryStatus: string
{
    /** Waiting for its first attempt or for a retry. */
    case PENDING = 'pending';
    /** The endpoint answered with a 2xx status. */
    case SUCCEEDED = 'succeeded';
    /** Every attempt failed. */
    case FAILED = 'failed';
    /** The endpoint was disabled or deleted before it succeeded. */
    case CANCELLED = 'cancelled';

    public function asLabel(): string
    {
        return match ($this) {
            self::PENDING   => 'Pending',
            self::SUCCEEDED => 'Succeeded',
            self::FAILED    => 'Failed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function asColor(): string
    {
        return match ($this) {
            self::PENDING   => 'amber',
            self::SUCCEEDED => 'green',
            self::FAILED    => 'red',
            self::CANCELLED => 'zinc',
        };
    }
}

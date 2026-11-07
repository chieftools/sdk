<?php

namespace ChiefTools\SDK\Webhook\Outbound\Enums;

use Dedoc\Scramble\Attributes\SchemaName;
use ChiefTools\SDK\Scramble\Attributes\ExtensibleEnum;

/**
 * Why an attempt failed without a usable response, an attempt with a non-2xx response has no error.
 */
#[ExtensibleEnum]
#[SchemaName('WebhookAttemptError')]
enum AttemptError: string
{
    /** The endpoint did not answer within 15 seconds. */
    case TIMEOUT = 'timeout';
    /** The connection failed: DNS, refused, TLS. */
    case CONNECTION = 'connection';
    /** The URL resolves to a private or reserved address. */
    case BLOCKED = 'blocked';
    /** The endpoint answered with a redirect, which is not followed. */
    case REDIRECT = 'redirect';

    public function asLabel(): string
    {
        return match ($this) {
            self::TIMEOUT    => 'Timed out',
            self::CONNECTION => 'Connection failed',
            self::BLOCKED    => 'Blocked address',
            self::REDIRECT   => 'Redirected',
        };
    }
}

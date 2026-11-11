<?php

namespace ChiefTools\SDK\Webhook\Outbound\Enums;

use ChiefTools\SDK\Webhook\Outbound\Attributes\WebhookObject;
use ChiefTools\SDK\Webhook\Outbound\Contracts\WebhookEventType;

/**
 * Events every app sends, only to the endpoint they are for and never to subscriptions.
 */
enum BuiltInEvent: string implements WebhookEventType
{
    /** Someone sent a test event to check the endpoint receives and verifies deliveries. */
    #[WebhookObject('array{id: string, url: string, description: string|null}', 'The endpoint the test event was sent to.')]
    case TEST = 'webhook_endpoint.test';

    public function group(): string
    {
        return 'Testing';
    }

    public function description(): string
    {
        return match ($this) {
            self::TEST => 'A test event sent from the dashboard.',
        };
    }
}

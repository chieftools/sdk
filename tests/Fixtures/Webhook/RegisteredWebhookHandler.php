<?php

namespace Tests\Fixtures\Webhook;

use ChiefTools\SDK\Webhook\Handlers\Handler;

class RegisteredWebhookHandler implements Handler
{
    public static ?array $payload = null;

    public function __invoke(array $payload): ?array
    {
        self::$payload = $payload;

        return [
            'status' => 'registered',
        ];
    }
}

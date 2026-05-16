<?php

use ChiefTools\SDK\Chief;
use Illuminate\Http\Request;
use ChiefTools\SDK\Webhook\WebhookEvent;
use ChiefTools\SDK\Http\Controllers\Webhook;
use ChiefTools\SDK\Webhook\Handlers\TeamUpdated;
use Tests\Fixtures\Webhook\RegisteredWebhookHandler;

it('resolves SDK webhook handlers with enum cases', function () {
    expect(Chief::getWebhookHandler(WebhookEvent::TEAM_UPDATED))->toBe(TeamUpdated::class);
});

it('handles webhooks registered at runtime', function () {
    RegisteredWebhookHandler::$payload = null;

    Chief::registerWebhookHandler(WebhookEvent::CHECKOUT_SESSION_COMPLETED, RegisteredWebhookHandler::class);

    $request = Request::create(
        uri: '/webhooks/chief',
        method: 'POST',
        server: [
            'CONTENT_TYPE' => 'application/json',
        ],
        content: json_encode([
            'event' => enum_value(WebhookEvent::CHECKOUT_SESSION_COMPLETED),
            'data'  => [
                'reference' => 'domain-prepayment-test',
            ],
        ], JSON_THROW_ON_ERROR),
    );

    expect(app(Webhook::class)($request))->toBe([
        'status' => 'registered',
    ])->and(RegisteredWebhookHandler::$payload)->toBe([
        'event' => enum_value(WebhookEvent::CHECKOUT_SESSION_COMPLETED),
        'data'  => [
            'reference' => 'domain-prepayment-test',
        ],
    ]);
});

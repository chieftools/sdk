<?php

use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use ChiefTools\SDK\API\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Client as HttpClient;

it('creates a checkout session', function () {
    config()->set('chief.id', 'domainchief');
    config()->set('chief.secret', 'secret');

    $history = [];
    $client  = checkoutSessionClient([
        new Response(200, [], json_encode([
            'id'         => 'checkout_123',
            'stripe_id'  => 'cs_test_123',
            'reference'  => 'domain-prepayment-test',
            'status'     => 'open',
            'url'        => 'https://checkout.stripe.test/cs_test_123',
            'total'      => 1033,
            'currency'   => 'EUR',
            'expires_at' => '2026-05-16T12:00:00+00:00',
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $checkoutSession = $client->createCheckoutSession(
        teamSlug: 'team-slug',
        reference: 'domain-prepayment-test',
        lines: [
            [
                'id'          => 'line_1',
                'description' => 'example.mock (registration)',
                'amount'      => 1033,
            ],
        ],
        successUrl: 'https://domain.chief.test/success',
        cancelUrl: 'https://domain.chief.test/cancel',
    );

    $requestBody = json_decode((string)$history[0]['request']->getBody(), true, 512, JSON_THROW_ON_ERROR);

    expect($checkoutSession)
        ->toBe([
            'id'         => 'checkout_123',
            'stripe_id'  => 'cs_test_123',
            'reference'  => 'domain-prepayment-test',
            'status'     => 'open',
            'url'        => 'https://checkout.stripe.test/cs_test_123',
            'total'      => 1033,
            'currency'   => 'EUR',
            'expires_at' => '2026-05-16T12:00:00+00:00',
        ])
        ->and($history[0]['request']->getMethod())->toBe('PUT')
        ->and($history[0]['request']->getUri()->getPath())->toBe('/api/team/team-slug/billing/checkout-session/domain-prepayment-test')
        ->and($history[0]['request']->getHeaderLine('X-Chief-App'))->toBe('domainchief')
        ->and($history[0]['request']->getHeaderLine('X-Chief-Secret'))->toBe('secret')
        ->and($requestBody)->toBe([
            'lines'       => [
                [
                    'id'          => 'line_1',
                    'description' => 'example.mock (registration)',
                    'amount'      => 1033,
                ],
            ],
            'success_url' => 'https://domain.chief.test/success',
            'cancel_url'  => 'https://domain.chief.test/cancel',
        ]);
});

it('retrieves a checkout session status', function () {
    config()->set('chief.id', 'domainchief');
    config()->set('chief.secret', 'secret');

    $history = [];
    $client  = checkoutSessionClient([
        new Response(200, [], json_encode([
            'id'         => 'checkout_123',
            'stripe_id'  => 'cs_test_123',
            'reference'  => 'domain-prepayment-test',
            'status'     => 'complete',
            'url'        => null,
            'total'      => 1033,
            'currency'   => 'EUR',
            'expires_at' => '2026-05-16T12:00:00+00:00',
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $checkoutSession = $client->retrieveCheckoutSession('team-slug', 'domain-prepayment-test');

    expect($checkoutSession)
        ->toBe([
            'id'         => 'checkout_123',
            'stripe_id'  => 'cs_test_123',
            'reference'  => 'domain-prepayment-test',
            'status'     => 'complete',
            'url'        => null,
            'total'      => 1033,
            'currency'   => 'EUR',
            'expires_at' => '2026-05-16T12:00:00+00:00',
        ])
        ->and($history[0]['request']->getMethod())->toBe('GET')
        ->and($history[0]['request']->getUri()->getPath())->toBe('/api/team/team-slug/billing/checkout-session/domain-prepayment-test')
        ->and($history[0]['request']->getHeaderLine('X-Chief-App'))->toBe('domainchief')
        ->and($history[0]['request']->getHeaderLine('X-Chief-Secret'))->toBe('secret');
});

it('expires a checkout session', function () {
    config()->set('chief.id', 'domainchief');
    config()->set('chief.secret', 'secret');

    $history = [];
    $client  = checkoutSessionClient([
        new Response(200, [], json_encode([
            'id'        => 'checkout_123',
            'stripe_id' => 'cs_test_123',
            'reference' => 'domain-prepayment-test',
            'status'    => 'expired',
            'total'     => 1033,
            'currency'  => 'EUR',
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $checkoutSession = $client->expireCheckoutSession('team-slug', 'domain-prepayment-test');

    expect($checkoutSession)
        ->toBe([
            'id'        => 'checkout_123',
            'stripe_id' => 'cs_test_123',
            'reference' => 'domain-prepayment-test',
            'status'    => 'expired',
            'total'     => 1033,
            'currency'  => 'EUR',
        ])
        ->and($history[0]['request']->getMethod())->toBe('DELETE')
        ->and($history[0]['request']->getUri()->getPath())->toBe('/api/team/team-slug/billing/checkout-session/domain-prepayment-test')
        ->and($history[0]['request']->getHeaderLine('X-Chief-App'))->toBe('domainchief')
        ->and($history[0]['request']->getHeaderLine('X-Chief-Secret'))->toBe('secret');
});

it('returns null when expiring a missing checkout session', function () {
    config()->set('chief.id', 'domainchief');
    config()->set('chief.secret', 'secret');

    $history = [];
    $client  = checkoutSessionClient([
        new Response(204),
    ], $history);

    expect($client->expireCheckoutSession('team-slug', 'domain-prepayment-test'))->toBeNull()
        ->and($history[0]['request']->getMethod())->toBe('DELETE');
});

/**
 * @param array<int, \GuzzleHttp\Psr7\Response>                                                                                                                           $responses
 * @param array<int, array{request: \Psr\Http\Message\RequestInterface, response: \Psr\Http\Message\ResponseInterface|null, error: mixed, options: array<string, mixed>}> $history
 */
function checkoutSessionClient(array $responses, array &$history): Client
{
    $mockHandler = new MockHandler($responses);
    $handler     = HandlerStack::create($mockHandler);
    $handler->push(Middleware::history($history));

    return new Client(new HttpClient([
        'base_uri' => 'https://account.chief.test',
        'handler'  => $handler,
    ]));
}

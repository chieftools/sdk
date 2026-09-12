<?php

use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use ChiefTools\SDK\API\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Client as HttpClient;
use ChiefTools\SDK\API\DTO\InvoiceLine;

it('sends optional invoice line resource metadata', function () {
    config()->set('chief.id', 'example-app');
    config()->set('chief.secret', 'synthetic-secret');

    $history = [];
    $handler = new MockHandler([
        new Response(200, [], json_encode([
            'stripe_id' => 'in_synthetic_1',
            'reference' => 'period-2099-05',
            'status'    => 'draft',
            'total'     => 1350,
            'currency'  => 'EUR',
            'line_mode' => 'detailed',
        ], JSON_THROW_ON_ERROR)),
    ]);
    $stack   = HandlerStack::create($handler);
    $stack->push(Middleware::history($history));

    $client = new Client(new HttpClient([
        'base_uri' => 'https://account.chief.test',
        'handler'  => $stack,
    ]));

    $client->createOrUpdateDraftInvoice(
        teamSlug: 'synthetic-team',
        reference: 'period-2099-05',
        lines: [
            new InvoiceLine(
                id: 'operation_synthetic_6',
                description: 'Synthetic resource renewal',
                amount: 1350,
                resourceId: 'resource_synthetic_3',
                resourceType: 'domain',
            ),
        ],
        memo: null,
    );

    $requestBody = json_decode((string)$history[0]['request']->getBody(), true, 512, JSON_THROW_ON_ERROR);

    expect($requestBody['lines'])->toBe([[
        'id'          => 'operation_synthetic_6',
        'description' => 'Synthetic resource renewal',
        'amount'      => 1350,
        'resource'    => [
            'id'   => 'resource_synthetic_3',
            'type' => 'domain',
        ],
    ]]);
});

it('queues finalization for every draft invoice with the same reference', function () {
    config()->set('chief.id', 'example-app');
    config()->set('chief.secret', 'synthetic-secret');

    $history = [];
    $handler = new MockHandler([
        new Response(202, [], json_encode([
            'status'    => 'queued',
            'reference' => 'period-2099-04',
        ], JSON_THROW_ON_ERROR)),
    ]);
    $stack   = HandlerStack::create($handler);
    $stack->push(Middleware::history($history));

    $client = new Client(new HttpClient([
        'base_uri' => 'https://account.chief.test',
        'handler'  => $stack,
    ]));

    expect($client->finalizeDraftInvoices('period-2099-04'))
        ->toBe([
            'status'    => 'queued',
            'reference' => 'period-2099-04',
        ])
        ->and($history[0]['request']->getMethod())->toBe('POST')
        ->and($history[0]['request']->getUri()->getPath())->toBe('/api/billing/invoices/period-2099-04/finalize')
        ->and($history[0]['request']->getHeaderLine('X-Chief-App'))->toBe('example-app')
        ->and($history[0]['request']->getHeaderLine('X-Chief-Secret'))->toBe('synthetic-secret');
});

<?php

use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use ChiefTools\SDK\API\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Client as HttpClient;

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

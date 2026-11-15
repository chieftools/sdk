<?php

use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use ChiefTools\SDK\API\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use ChiefTools\SDK\API\DTO\AppNotification;
use ChiefTools\SDK\Jobs\Notifications\SendAppNotification;

function appNotificationClient(array $responses, array &$history): Client
{
    $handler = HandlerStack::create(new MockHandler($responses));
    $handler->push(Middleware::history($history));

    return new Client(new HttpClient([
        'base_uri' => 'https://account.chief.test',
        'handler'  => $handler,
    ]));
}

function renewedNotification(): AppNotification
{
    return new AppNotification(
        users: ['8f3a6c1e-2b4d-4e5f-9a7b-1c2d3e4f5a6b'],
        type: 'domain.renewed',
        title: 'Domain renewed',
        body: 'lantern.example was renewed until 15 November 2027.',
        idempotencyKey: 'domainchief.activity/42',
        team: 'amber-grove',
        path: '/domains/lantern.example',
    );
}

it('sends an app notification to the mothership', function () {
    config()->set('chief.id', 'domainchief');
    config()->set('chief.secret', 'secret');

    $history = [];
    $outcome = appNotificationClient([
        new Response(202, [], json_encode(['queued' => 1, 'muted' => 0, 'unavailable' => 0, 'duplicate' => 0, 'unknown' => 0], JSON_THROW_ON_ERROR)),
    ], $history)->sendAppNotification(renewedNotification());

    expect($outcome['queued'])->toBe(1)
        ->and($history[0]['request']->getMethod())->toBe('POST')
        ->and($history[0]['request']->getUri()->getPath())->toBe('/api/notifications')
        ->and($history[0]['request']->getHeaderLine('X-Chief-App'))->toBe('domainchief')
        ->and($history[0]['request']->getHeaderLine('X-Chief-Secret'))->toBe('secret')
        ->and(json_decode((string)$history[0]['request']->getBody(), true, 512, JSON_THROW_ON_ERROR))->toBe([
            'users'           => ['8f3a6c1e-2b4d-4e5f-9a7b-1c2d3e4f5a6b'],
            'team'            => 'amber-grove',
            'type'            => 'domain.renewed',
            'title'           => 'Domain renewed',
            'body'            => 'lantern.example was renewed until 15 November 2027.',
            'path'            => '/domains/lantern.example',
            'idempotency_key' => 'domainchief.activity/42',
            'time_sensitive'  => false,
        ]);
});

it('goes to 1 to 100 users', function (int $count) {
    new AppNotification(
        users: $count === 0 ? [] : array_map(static fn (int $i): string => "user-{$i}", range(1, $count)),
        type: 'domain.renewed',
        title: 'Domain renewed',
        body: 'Renewed.',
        idempotencyKey: 'key',
    );
})->with([0, 101])->throws(InvalidArgumentException::class);

it('only retries a refused notification when it was rate limited', function () {
    config()->set('chief.id', 'domainchief');
    config()->set('chief.secret', 'secret');

    $history = [];
    app()->instance(Client::class, appNotificationClient([
        new Response(422, [], json_encode(['message' => 'The team does not exist or does not use this app.'], JSON_THROW_ON_ERROR)),
        new Response(429, ['Retry-After' => '30'], '{}'),
    ], $history));

    // Refused: the job fails without throwing, so it is not retried
    app()->call([new SendAppNotification(renewedNotification()), 'handle']);

    expect(fn () => app()->call([new SendAppNotification(renewedNotification()), 'handle']))->toThrow(ClientException::class)
        ->and($history)->toHaveCount(2);
});

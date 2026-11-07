<?php

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Generator;
use ChiefTools\SDK\Scramble\WebhooksDocumentTransformer;

function webhooksDocument(): array
{
    Scramble::registerApi('webhooks-test', ['api_path' => 'nothing-here'])
        ->routes(static fn (): bool => false)
        ->withDocumentTransformers(WebhooksDocumentTransformer::class);

    return app(Generator::class)(Scramble::getGeneratorConfig('webhooks-test'));
}

it('documents every event as a webhook whose body is the event\'s schema', function () {
    $document = webhooksDocument();

    expect(array_keys($document['webhooks']))->toBe(['lantern.lit', 'lantern.dimmed', 'harbor.flooded', 'webhook_endpoint.test'])
        ->and($document['webhooks']['lantern.lit']['post'])->toMatchArray([
            'operationId' => 'webhook.lantern.lit',
            'summary'     => 'A lantern was lit.',
            'tags'        => ['Lanterns'],
            'requestBody' => [
                'required' => true,
                'content'  => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/LanternLitEvent']]],
            ],
            'parameters'  => [
                ['$ref' => '#/components/parameters/WebhookId'],
                ['$ref' => '#/components/parameters/WebhookTimestamp'],
                ['$ref' => '#/components/parameters/WebhookSignature'],
            ],
        ])
        ->and($document['webhooks']['lantern.lit']['post']['responses'])->toHaveKey('2XX')
        ->and($document['components']['parameters']['WebhookSignature'])->toMatchArray(['name' => 'webhook-signature', 'in' => 'header', 'required' => true]);
});

it('describes the envelope with the object as the API returns it and the extra data fields', function () {
    $event = webhooksDocument()['components']['schemas']['LanternLitEvent'];

    expect($event['required'])->toBe(['id', 'type', 'timestamp', 'api_version', 'data'])
        ->and($event['properties']['type']['enum'])->toBe(['lantern.lit'])
        ->and($event['properties']['id']['examples'])->toBe(['event_01m6v3f5aydrktmzrbpz1pqh7e'])
        ->and($event['properties']['timestamp']['format'])->toBe('date-time')
        ->and($event['properties']['data']['required'])->toBe(['object', 'lit_at'])
        ->and($event['properties']['data']['properties']['object']['$ref'])->toBe('#/components/schemas/Lantern')
        ->and($event['properties']['data']['properties']['lit_at'])->toMatchArray(['type' => 'string', 'format' => 'date-time', 'description' => 'When it was lit.'])
        ->and($event['properties']['data']['properties']['colors'])->toMatchArray([
            'type'        => 'array',
            'items'       => ['$ref' => '#/components/schemas/LanternColor'],
            'description' => 'The colors it shines in.',
        ]);
});

it('takes the object from a PHPDoc type, or leaves it open without one', function () {
    $schemas = webhooksDocument()['components']['schemas'];

    expect($schemas['HarborFloodedEvent']['properties']['data']['properties']['object'])->toMatchArray([
        'type'       => 'object',
        'properties' => ['id' => ['type' => 'string'], 'depth' => ['type' => 'number']],
    ])
        ->and($schemas['LanternDimmedEvent']['properties']['data']['properties']['object']['type'])->toBe('object')
        ->and($schemas['WebhookEndpointTestEvent']['properties']['data']['properties']['object']['properties'])->toHaveKeys(['id', 'url', 'description']);
});

it('documents no webhooks while webhooks are disabled for the app', function () {
    config(['chief.webhooks.enabled' => false]);

    expect(webhooksDocument())->not->toHaveKey('webhooks');
});

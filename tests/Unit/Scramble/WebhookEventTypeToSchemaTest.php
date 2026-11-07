<?php

use Dedoc\Scramble\Infer;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\GeneratorConfig;
use Dedoc\Scramble\Support\Generator\OpenApi;
use ChiefTools\SDK\Scramble\WebhookEventTypeToSchema;
use Dedoc\Scramble\Support\Generator\TypeTransformer;
use Dedoc\Scramble\Support\Type\ObjectType as InferObjectType;
use ChiefTools\SDK\Webhook\Outbound\Contracts\WebhookEventType;

it('documents event types as an extensible enum of the app\'s events and the built-in ones', function () {
    $document    = OpenApi::make('3.1.0');
    $transformer = new TypeTransformer(app(Infer::class), new OpenApiContext($document, new GeneratorConfig('test')), [WebhookEventTypeToSchema::class]);

    $reference = $transformer->transform(new InferObjectType(WebhookEventType::class));
    $schema    = $document->components->getSchema('WebhookEventType')->type;

    expect($reference->toArray())->toBe(['$ref' => '#/components/schemas/WebhookEventType'])
        ->and($schema->enum)->toBe([])
        ->and($schema->getExtensionProperty('extensible-enum'))->toBe(['lantern.lit', 'lantern.dimmed', 'harbor.flooded', 'webhook_endpoint.test'])
        ->and($schema->description)->toContain('| `harbor.flooded` <br/> A harbor flooded. |');
});

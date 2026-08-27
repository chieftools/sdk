<?php

namespace Tests\Unit\Scramble;

use Dedoc\Scramble\Infer;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\GeneratorConfig;
use Dedoc\Scramble\Attributes\SchemaName;
use Dedoc\Scramble\Support\Generator\OpenApi;
use ChiefTools\SDK\Scramble\ExtensibleEnumToSchema;
use Dedoc\Scramble\Support\Generator\TypeTransformer;
use ChiefTools\SDK\Scramble\Attributes\ExtensibleEnum;
use Dedoc\Scramble\Support\Type\ObjectType as InferObjectType;
use Dedoc\Scramble\Support\TypeToSchemaExtensions\EnumToSchema;

#[ExtensibleEnum]
#[SchemaName('SyntheticExtensibleState')]
enum SyntheticExtensibleState: string
{
    case Queued = 'synthetic.queued';
    case Ready  = 'synthetic.ready';
}

#[SchemaName('SyntheticFixedState')]
enum SyntheticFixedState: string
{
    case Enabled  = 'synthetic.enabled';
    case Disabled = 'synthetic.disabled';
}

it('documents attributed enums as extensible schemas', function () {
    $document    = OpenApi::make('3.1.0');
    $context     = new OpenApiContext($document, new GeneratorConfig('test'));
    $transformer = new TypeTransformer(
        app(Infer::class),
        $context,
        [EnumToSchema::class, ExtensibleEnumToSchema::class],
    );

    $transformer->transform(new InferObjectType(SyntheticExtensibleState::class));
    $transformer->transform(new InferObjectType(SyntheticFixedState::class));

    $extensibleType = $document->components->getSchema('SyntheticExtensibleState')->type;
    $fixedType      = $document->components->getSchema('SyntheticFixedState')->type;

    expect($extensibleType->enum)->toBe([])
        ->and($extensibleType->getExtensionProperty('extensible-enum'))->toBe([
            'synthetic.queued',
            'synthetic.ready',
        ])
        ->and($fixedType->enum)->toBe([
            'synthetic.enabled',
            'synthetic.disabled',
        ])
        ->and($fixedType->hasExtensionProperty('extensible-enum'))->toBeFalse();
});

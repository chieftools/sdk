<?php

namespace Tests\Unit\Scramble;

use Dedoc\Scramble\GeneratorConfig;
use Illuminate\Validation\Rules\Enum;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use ChiefTools\SDK\Scramble\ClosedExtensibleEnumInputRule;
use Dedoc\Scramble\Support\RuleTransforming\NormalizedRule;
use Dedoc\Scramble\Support\RuleTransforming\RuleTransformerContext;

enum SyntheticInputState: string
{
    case Awaiting = 'synthetic.awaiting';
    case Finished = 'synthetic.finished';
}

it('closes extensible enum component references for request validation', function ($componentType, array $knownValues, string $expectedType) {
    $document = OpenApi::make('3.1.0');
    $componentType->setDescription('Synthetic input state.');
    $componentType->setExtensionProperty('extensible-enum', $knownValues);
    $document->components->addSchema('SyntheticInputState', Schema::fromType($componentType));

    $reference = $document->components->getSchemaReference('SyntheticInputState');
    $reference->nullable(true);

    $rule    = NormalizedRule::fromValue(new Enum(SyntheticInputState::class));
    $context = new RuleTransformerContext(
        'state',
        collect(),
        $document,
        new GeneratorConfig('test'),
    );

    $result = (new ClosedExtensibleEnumInputRule)->toSchema($reference, $rule, $context);

    expect($result)->toBeInstanceOf($expectedType)
        ->and($result->enum)->toBe($knownValues)
        ->and($result->nullable)->toBeTrue()
        ->and($result->description)->toBe('Synthetic input state.')
        ->and($result->hasExtensionProperty('extensible-enum'))->toBeFalse();
})->with([
    'string-backed enum'  => [new StringType, ['synthetic.awaiting', 'synthetic.finished'], StringType::class],
    'integer-backed enum' => [new IntegerType, [10, 20], IntegerType::class],
]);

it('leaves closed enum component references unchanged', function () {
    $document = OpenApi::make('3.1.0');
    $document->components->addSchema(
        'SyntheticInputState',
        Schema::fromType((new StringType)->enum(['synthetic.awaiting', 'synthetic.finished'])),
    );

    $reference = $document->components->getSchemaReference('SyntheticInputState');
    $rule      = NormalizedRule::fromValue(new Enum(SyntheticInputState::class));
    $context   = new RuleTransformerContext(
        'state',
        collect(),
        $document,
        new GeneratorConfig('test'),
    );

    $result = (new ClosedExtensibleEnumInputRule)->toSchema($reference, $rule, $context);

    expect($result)->toBe($reference);
});

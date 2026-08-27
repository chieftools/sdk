<?php

namespace ChiefTools\SDK\Scramble;

use Illuminate\Validation\Rules\Enum;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Contracts\RuleTransformer;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\Types\Type;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\RuleTransforming\NormalizedRule;
use Dedoc\Scramble\Support\RuleTransforming\RuleTransformerContext;

final class ClosedExtensibleEnumInputRule implements RuleTransformer
{
    public function shouldHandle(NormalizedRule $rule): bool
    {
        return $rule->is(Enum::class);
    }

    public function toSchema(Type $previous, NormalizedRule $rule, RuleTransformerContext $context): Type
    {
        if (!$previous instanceof Reference) {
            return $previous;
        }

        $component = $previous->resolve();

        if (!$component instanceof Schema) {
            return $previous;
        }

        $knownValues = $component->type->getExtensionProperty('extensible-enum');

        if (!is_array($knownValues) || $knownValues === []) {
            return $previous;
        }

        $closedType = match ($component->type->type) {
            'integer' => new IntegerType,
            'string'  => new StringType,
            default   => null,
        };

        if ($closedType === null) {
            return $previous;
        }

        $closedType->enum($knownValues);
        $closedType->nullable($previous->nullable);
        $closedType->setDescription($component->type->description);

        return $closedType;
    }
}

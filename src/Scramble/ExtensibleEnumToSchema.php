<?php

namespace ChiefTools\SDK\Scramble;

use ReflectionEnum;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Support\Type\ObjectType;
use ChiefTools\SDK\Scramble\Attributes\ExtensibleEnum;
use Dedoc\Scramble\Support\Generator\Types as OpenApi;
use Dedoc\Scramble\Support\TypeToSchemaExtensions\EnumToSchema;

class ExtensibleEnumToSchema extends EnumToSchema
{
    /** @param \Dedoc\Scramble\Support\Type\ObjectType $type */
    public function toSchema(Type $type): OpenApi\Type
    {
        $schema      = parent::toSchema($type);
        $knownValues = $schema->enum;

        $schema->enum([]);
        $schema->setExtensionProperty('extensible-enum', $knownValues);

        return $schema;
    }

    public function shouldHandle(Type $type): bool
    {
        return parent::shouldHandle($type)
            && $type instanceof ObjectType
            && !empty((new ReflectionEnum($type->name))->getAttributes(ExtensibleEnum::class));
    }
}

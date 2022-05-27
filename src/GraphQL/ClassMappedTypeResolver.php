<?php

namespace IronGate\Chief\GraphQL;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Schema\TypeRegistry;

abstract class ClassMappedTypeResolver
{
    protected static array $classMap = [];

    protected TypeRegistry $typeRegistry;

    public function __construct(TypeRegistry $typeRegistry)
    {
        $this->typeRegistry = $typeRegistry;
    }

    public function __invoke($rootValue, $context, ResolveInfo $info): Type
    {
        if (is_array($rootValue) && isset($rootValue['__typename'])) {
            return $this->typeRegistry->get($rootValue['__typename']);
        }

        if ($type = (static::$classMap[$rootValue::class] ?? null)) {
            return $this->typeRegistry->get($type);
        }

        return $this->typeRegistry->get(class_basename($rootValue));
    }
}

<?php

namespace ChiefTools\SDK\GraphQL\Directives;

use GraphQL\Language\AST\TypeDefinitionNode;
use GraphQL\Language\AST\FieldDefinitionNode;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Language\AST\InterfaceTypeDefinitionNode;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Support\Contracts\TypeManipulator;
use Nuwave\Lighthouse\Support\Contracts\FieldManipulator;

class LocalDirective extends BaseDirective implements FieldManipulator, TypeManipulator
{
    private static bool $markedAsLocal = true;

    public static function markRequestAsFederated(): void
    {
        self::$markedAsLocal = false;
    }

    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'GRAPHQL'
"""
Mark the field or object as local only, it will be removed from the graph when a federated request comes in.
"""
directive @local repeatable on FIELD_DEFINITION | OBJECT
GRAPHQL;
    }

    public function manipulateFieldDefinition(DocumentAST &$documentAST, FieldDefinitionNode &$fieldDefinition, ObjectTypeDefinitionNode|InterfaceTypeDefinitionNode &$parentType): void
    {
        if (self::$markedAsLocal) {
            return;
        }

        foreach ($parentType->fields as $key => $value) {
            if ($fieldDefinition === $value) {
                unset($parentType->fields[$key]);

                break;
            }
        }
    }

    public function manipulateTypeDefinition(DocumentAST &$documentAST, TypeDefinitionNode &$typeDefinition): void
    {
        if (self::$markedAsLocal) {
            return;
        }

        unset($documentAST->types[$typeDefinition->getName()->value]);
    }
}

<?php

namespace ChiefTools\SDK\GraphQL\Directives;

use RuntimeException;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Language\AST\TypeDefinitionNode;
use GraphQL\Language\AST\FieldDefinitionNode;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use GraphQL\Language\AST\EnumTypeDefinitionNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Language\AST\InterfaceTypeDefinitionNode;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use GraphQL\Language\AST\InputObjectTypeDefinitionNode;
use Nuwave\Lighthouse\Support\Contracts\TypeManipulator;
use Nuwave\Lighthouse\Support\Contracts\FieldManipulator;

class AutoDocumentDirective extends BaseDirective implements TypeManipulator, FieldManipulator
{
    public function manipulateTypeDefinition(DocumentAST &$documentAST, TypeDefinitionNode &$typeDefinition): void
    {
        $type   = $this->directiveArgValue('type');
        $value  = null;
        $plural = true;

        /** @phpstan-ignore property.notFound */
        $definitionName = $typeDefinition->name->value;

        if ($typeDefinition instanceof InputObjectTypeDefinitionNode) {
            if (str_ends_with($definitionName, 'Order')) {
                $type ??= snake_case(str_before($definitionName, 'Order'), ' ');

                $value = 'Ways in which lists of %s can be ordered upon return.';

                $this->addFieldDocumentationForOrderInput($typeDefinition, $type);
            }
        }

        if ($typeDefinition instanceof EnumTypeDefinitionNode) {
            if (str_ends_with($definitionName, 'BulkAction')) {
                $type ??= snake_case(str_before($definitionName, 'BulkAction'), ' ');

                $value  = 'Possible %s bulk actions.';
                $plural = false;
            } elseif (str_ends_with($definitionName, 'OrderField')) {
                $type ??= snake_case(str_before($definitionName, 'OrderField'), ' ');

                $value = 'Properties by which %s can be ordered.';
            } elseif (str_ends_with($definitionName, 'Status')) {
                $type ??= snake_case(str_before($definitionName, 'Status'), ' ');

                $value  = 'Possible %s statuses.';
                $plural = false;
            } elseif (str_ends_with($definitionName, 'Source')) {
                $type ??= snake_case(str_before($definitionName, 'Source'), ' ');

                $value  = 'Possible %s sources.';
                $plural = false;
            } elseif (str_ends_with($definitionName, 'State')) {
                $type ??= snake_case(str_before($definitionName, 'State'), ' ');

                $value  = 'Possible %s states.';
                $plural = false;
            } elseif (str_ends_with($definitionName, 'Type')) {
                $type ??= snake_case(str_before($definitionName, 'Type'), ' ');

                $value  = 'Possible %s types.';
                $plural = false;
            }
        }

        if ($value === null) {
            throw new RuntimeException("{$definitionName} cannot be auto documented at this time!");
        }

        $typeDefinition->description = new StringValueNode([
            'value' => sprintf($value, $plural ? str_plural($type) : $type),
            'block' => false,
        ]);
    }

    public function manipulateFieldDefinition(DocumentAST &$documentAST, FieldDefinitionNode &$fieldDefinition, ObjectTypeDefinitionNode|InterfaceTypeDefinitionNode &$parentType): void
    {
        $value = null;

        switch ($fieldDefinition->name->value) {
            case 'id':
                $value = 'The unique identifier for the object.';
                break;
            case 'createdAt':
                $value = 'Identifies the date and time when the object was created.';
                break;
            case 'updatedAt':
                $value = 'Identifies the date and time when the object was updated.';
                break;
            case 'deletedAt':
                $value = 'Identifies the date and time when the object was deleted.';
                break;
        }

        if ($value === null) {
            throw new RuntimeException("{$fieldDefinition->name->value} cannot be auto documented at this time!");
        }

        $fieldDefinition->description = new StringValueNode([
            'value' => $value,
            'block' => false,
        ]);
    }

    private function addFieldDocumentationForOrderInput(InputObjectTypeDefinitionNode $node, string $type): void
    {
        foreach ($node->fields as $field) {
            switch ($field->name->value) {
                case 'field':
                    $field->description = new StringValueNode([
                        'value' => sprintf('The field to order %s by.', str_plural($type)),
                        'block' => false,
                    ]);
                    break;
                case 'direction':
                    $field->description = new StringValueNode([
                        'value' => 'The ordering direction.',
                        'block' => false,
                    ]);
                    break;
            }
        }
    }

    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'GRAPHQL'
        """
        Automatically document a type.
        """
        directive @autoDocument(
            "Override the auto generated type name."
            type: String
        ) on INPUT_OBJECT | ENUM | FIELD_DEFINITION
        GRAPHQL;
    }
}

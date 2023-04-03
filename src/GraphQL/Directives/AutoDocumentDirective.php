<?php

namespace ChiefTools\SDK\GraphQL\Directives;

use RuntimeException;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Language\AST\TypeDefinitionNode;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use GraphQL\Language\AST\EnumTypeDefinitionNode;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use GraphQL\Language\AST\InputObjectTypeDefinitionNode;
use Nuwave\Lighthouse\Support\Contracts\TypeManipulator;

class AutoDocumentDirective extends BaseDirective implements TypeManipulator
{
    public function manipulateTypeDefinition(DocumentAST &$documentAST, TypeDefinitionNode &$typeDefinition): void
    {
        $type   = $this->directiveArgValue('type');
        $value  = null;
        $plural = true;

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
        ) on INPUT_OBJECT | ENUM
        GRAPHQL;
    }
}

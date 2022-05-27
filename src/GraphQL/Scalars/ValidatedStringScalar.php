<?php

namespace IronGate\Chief\GraphQL\Scalars;

use GraphQL\Error\Error;
use GraphQL\Utils\Utils;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Language\AST\StringValueNode;
use Illuminate\Contracts\Validation\Rule;

abstract class ValidatedStringScalar extends ScalarType
{
    protected static array $validationRules;

    public static function getValidationRules(): array
    {
        $rules = [];

        foreach (static::$validationRules as $validationRule) {
            // Allow for defining a validation rule class name
            if (class_exists($validationRule)) {
                $rule = new $validationRule;

                if ($rule instanceof Rule) {
                    $validationRule = $rule;
                }
            }

            $rules[] = $validationRule;
        }

        return $rules;
    }

    public function serialize($value)
    {
        return $value;
    }

    public function parseValue($value)
    {
        foreach (static::getValidationRules() as $validationRule) {
            if (validate($value, $validationRule)) {
                return $value;
            }
        }

        throw new Error('Cannot represent following value as a ' . class_basename($this) . ': ' . Utils::printSafeJson($value));
    }

    public function parseLiteral($valueNode, ?array $variables = null)
    {
        if (!$valueNode instanceof StringValueNode) {
            throw new Error('Query error: Can only parse strings got: ' . $valueNode->kind, [$valueNode]);
        }

        if (!validate($valueNode->value, static::getValidationRules())) {
            throw new Error('Not a valid ' . class_basename($this), [$valueNode]);
        }

        return $this->parseValue($valueNode->value);
    }
}

<?php

namespace IronGate\Integration\GraphQL\Scalars;

use GraphQL\Error\Error;
use GraphQL\Utils\Utils;
use Illuminate\Validation\Rule;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Language\AST\StringValueNode;

abstract class ValidatedStringScalar extends ScalarType
{
    protected static $validationRule;

    public static function getValidationRule()
    {
        if (class_exists(static::$validationRule)) {
            $rule = new static::$validationRule;

            if ($rule instanceof Rule) {
                return $rule;
            }
        }

        return static::$validationRule;
    }

    public function serialize($value)
    {
        return $value;
    }

    public function parseValue($value)
    {
        if (!validate($value, static::getValidationRule())) {
            throw new Error('Cannot represent following value as a ' . class_basename($this) . ': ' . Utils::printSafeJson($value));
        }

        return $value;
    }

    public function parseLiteral($valueNode, ?array $variables = null)
    {
        if (!$valueNode instanceof StringValueNode) {
            throw new Error('Query error: Can only parse strings got: ' . $valueNode->kind, [$valueNode]);
        }

        if (!validate($valueNode->value, static::getValidationRule())) {
            throw new Error('Not a valid ' . class_basename($this), [$valueNode]);
        }

        return $this->parseValue($valueNode->value);
    }
}

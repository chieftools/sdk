<?php

namespace IronGate\Integration\GraphQL\Scalars;

use GraphQL\Error\Error;
use GraphQL\Utils\Utils;
use Illuminate\Validation\Rule;

abstract class ValidatedStringScalar extends Str
{
    /**
     * Either a single validation rule or array of rules.
     *
     * @var string|array
     */
    protected static $validationRule;

    /**
     * Retrieve the validation rule or rules.
     *
     * @return array
     */
    private static function getValidationRules(): array
    {
        $rules = [];

        foreach ((array)static::$validationRule as $validationRule) {
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

    /**
     * {@inheritdoc}
     */
    public function parseValue($value)
    {
        foreach (static::getValidationRules() as $validationRule) {
            if (!validate($value, $validationRule)) {
                return parent::parseValue($value);
            }
        }

        throw new Error('Cannot represent following value as a ' . class_basename($this) . ': ' . Utils::printSafeJson($value));
    }

    /**
     * {@inheritdoc}
     */
    public function parseLiteral($valueNode, ?array $variables = null)
    {
        $return = parent::parseLiteral($valueNode, $variables);

        foreach (static::getValidationRules() as $validationRule) {
            if (!validate($valueNode->value, $validationRule)) {
                return $return;
            }
        }

        throw new Error('Not a valid ' . class_basename($this), [$valueNode]);
    }
}
